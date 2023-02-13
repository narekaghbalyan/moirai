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

    protected array $groupDirections = [
        'asc', 'desc'
    ];



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

    protected function devastateBindings(): void
    {
        $this->bindings = [];
    }



    protected function checkMatching(string|int|float|array $suspect, array $dataFromWhichToCheck): bool
    {
        if (is_array($suspect)) {
            foreach ($suspect as $item) {
                if (!in_array((int)$item, $dataFromWhichToCheck)) {
                    return false;
                }
            }
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
        return $this->concludeEntities($subject, $this->driver->getPitaForColumns());
    }

    protected function wrapStringInPita(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, $this->driver->getPitaForStrings());
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








    protected function changeQueryTypeToInsert(string $bindingName)
    {
        $table = $this->getBinding('from');

        $this->bindings = [
            $bindingName => ['into', $table]
        ];
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

    protected function throwExceptionIfDirectionIsInvalid(string $direction)
    {
        if (!$this->checkMatching($direction, $this->groupDirections)) {
            throw new Exception(
                '"' . $direction . '" is not a SQL group direction.'
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