<?php

namespace Moarai\QueryBuilder;

use Exception;
use Moarai\Drivers\AvailableDbmsDrivers;
use ReflectionClass;

trait ClauseBindersToolkit
{
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>', '&~', 'is', 'is not',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
        'in', 'between'
    ];

    protected array $bitwiseOperators = [
        '&', '|', '^', '<<', '>>', '&~',
    ];

    protected array $logicalOperators = [
        'and', 'or', 'not'
    ];

    protected array $orderDirections = [
        'asc', 'desc'
    ];


    // left outer = left
    // right outer = right
    // full outer = full
    protected array $joinTypes = [
        'left outer', 'right outer', 'full outer', 'inner', 'cross'
    ];

    private function useAdditionalAccessories(): void
    {
        $additionalAccessories = $this->driver->getAdditionalAccessories();

        if (!empty($additionalAccessories)) {
            foreach ($additionalAccessories as $accessoryName => $accessory) {
                $this->$accessoryName = array_merge($this->$accessoryName, $accessory);
            }
        }
    }

    protected function bind(string $bindingName, array $binding): void
    {
        $this->bindings[$bindingName][] = $binding;
    }

    protected function replaceBind(string $bindingName, array $binding): void
    {
        $this->bindings[$bindingName] = $binding;
    }

    protected function getBinding(string $bindingName): mixed
    {
        return $this->bindings[$bindingName];
    }

    protected function getBindings(): array
    {
        return $this->bindings;
    }

    protected function devastateBinding(string $bindingName): void
    {
        $this->bindings[$bindingName] = [];
    }

    protected function devastateBindings(): void
    {
        $this->bindings = [];
    }

    protected function deleteBinding(string $bindingName): void
    {
        unset($this->bindings[$bindingName]);
    }

    protected function resetBindingsToDefault(): void
    {
        $this->bindings = [
            'select' => [],
            'from' => [],
            'join' => [],
            'where' => [],
            'union' => [],
            'groupBy' => [],
            'having' => [],
            'orderBy' => [],
            'unionOrder' => [],
            'limit' => [],
            'offset' => []
        ];
    }

    protected function renameBinding(string $bindingName, string $bindingNewName): void
    {
        if (!array_key_exists($bindingName, $this->bindings)) {
            throw new Exception('Binding called "' . $bindingName . '" doesnt exist.');
        }

        $keys = array_keys($this->bindings);

        $keys[array_search($bindingName, $keys)] = $bindingNewName;

        $this->bindings = array_combine($keys, $this->bindings);
    }

    protected function changeQueryTypeToInsert(): void
    {
        $this->changeQueryType('insert');
    }

    protected function changeQueryTypeToUpdate(): void
    {
        $this->changeQueryType('update', false);
    }


    protected function changeQueryType(string $bindingName, bool $useInto = true): void
    {
        $table = $this->getBinding('from');

        $this->bindings = [$bindingName => $table];

        if ($useInto) {
            array_unshift($this->bindings[$bindingName], 'into');
        }
    }


    protected function getTableBinding(): string
    {
        $fromBinding = $this->getBinding('from');

        $table = null;

        array_walk_recursive($fromBinding, function ($item) use (&$table) {
            $table = $item;
        });

        return $table;
    }


    protected function checkMatching(string|int|float|array $suspect, array $dataFromWhichToCheck): bool
    {
        if (is_array($suspect)) {
            foreach ($suspect as $item) {
                if (!in_array($item, $dataFromWhichToCheck)) {
                    return false;
                }
            }

            return true;
        }

        return in_array($suspect, $dataFromWhichToCheck);
    }


    protected function isAssociative(array $array): bool
    {
        $supposedKeys = range(0, count($array) - 1);

        return array_keys($array) !== $supposedKeys;
    }


    protected function wrapColumnInPita(string|array $subject): string|array
    {
        $pitaForColumns = $this->driver->getPitaForColumns();

        return $this->concludeEntities($subject, $pitaForColumns['opening'], $pitaForColumns['closing']);
    }

    protected function wrapStringInPita(string|array $subject): string|array
    {
        $pitaForStrings = $this->driver->getPitaForStrings();

        return $this->concludeEntities($subject, $pitaForStrings['opening'], $pitaForStrings['closing']);
    }


    protected function concludeSingleQuotes(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, "'");
    }

    protected function concludeDoubleQuotes(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '"');
    }

    protected function concludeGraveAccent(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '`');
    }

    protected function concludeBrackets(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '(', ')');
    }

    private function concludeEntities(string|array $subject, string $openSymbol, string $closingSymbol = null): string|array
    {
        $flattenedSubject = [];

        if (is_null($closingSymbol)) {
            $closingSymbol = $openSymbol;
        }

        if (is_string($subject)) {
            return $openSymbol . $subject . $closingSymbol;
        } else {
            array_walk_recursive($subject, function ($item) use (&$flattenedSubject, $openSymbol, $closingSymbol) {
                $flattenedSubject[] = $openSymbol . $item . $closingSymbol;
            });
        }

        return $flattenedSubject;
    }

    private function supplementDirection(string $direction): string
    {
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = match (strtolower($direction)) {
                'nulls first' => 'ASC ' . $direction,
                'nulls last' => 'DESC ' . $direction
            };
        }

        return $direction;
    }


    protected function runCallback(string $bindingName, string $whereLogicalType, callable $callback): void
    {
        $this->bind($bindingName, [$whereLogicalType]);

        $this->bind($bindingName, ['(']);

        $callback($this);

        $this->bind($bindingName, [')']);
    }

    protected function runCallbackForVirginInstance(string $bindingName, callable $callback): void
    {
        $this->bind($bindingName, ['(']);

        $virginInstance = new $this($this->connection);

        $callback($virginInstance);

        $this->bind($bindingName, [$virginInstance]);

        $this->bind($bindingName, [')']);
    }


    protected function throwExceptionIfMisplacedArray(mixed $subject): void
    {
        if (is_array($subject)) {
            throw new Exception('Invalid value specified. The value cannot be an array.');
        }
    }

    protected function throwExceptionIfArrayAssociative(array $array, string|null $message = null)
    {
        if ($this->isAssociative($array)) {
            throw new Exception(
                is_null($message) ? 'Array cannot be associative.' : $message
            );
        }
    }

    protected function throwExceptionIfArrayIsNotAssociative(array $array, string|null $message = null)
    {
        if (!$this->isAssociative($array)) {
            throw new Exception(
                is_null($message) ? 'Array must be associative.' : $message
            );
        }
    }


    protected function throwExceptionIfOperatorIsInvalid(string $operator)
    {
        if (!$this->checkMatching($operator, $this->operators)) {
            throw new Exception(
                '"' . $operator . '" is not a SQL operator.'
            );
        }
    }

    protected function throwExceptionIfDirectionIsInvalid(string|array $direction)
    {
        if (!$this->checkMatching($direction, $this->orderDirections)) {
            throw new Exception(
                'Direction values for "order by" expression are not valid.'
            );
        }
    }

    protected function throwExceptionIfFtsModifierIsInvalid(string $modifier)
    {
        $reflectionClass = new ReflectionClass(FullTextSearchModifiers::class);

        if (!$this->checkMatching($modifier, $reflectionClass->getConstants())) {
            throw new Exception(
                '"' . $modifier . '" is not a modifier.'
            );
        }
    }
}