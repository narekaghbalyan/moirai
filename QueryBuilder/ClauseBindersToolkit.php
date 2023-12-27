<?php

namespace Moirai\QueryBuilder;

use Exception;
use Moirai\Drivers\AvailableDbmsDrivers;
use ReflectionClass;

trait ClauseBindersToolkit
{
    /**
     * @var array|string[]
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>', '&~', 'is', 'is not',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
        'in', 'between'
    ];

    /**
     * @var array|string[]
     */
    protected array $bitwiseOperators = [
        '&', '|', '^', '<<', '>>', '&~',
    ];

    /**
     * @var array|string[]
     */
    protected array $logicalOperators = [
        'and', 'or', 'not'
    ];

    /**
     * @var array|string[]
     */
    protected array $orderDirections = [
        'asc', 'desc'
    ];

    // left outer = left
    // right outer = right
    // full outer = full
    /**
     * @var array|string[]
     */
    protected array $joinTypes = [
        'left outer', 'right outer', 'full outer', 'inner', 'cross'
    ];

    /**
     * @return array
     */
    protected function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @param string $bindingName
     * @return mixed
     */
    protected function getBinding(string $bindingName): mixed
    {
        return $this->bindings[$bindingName];
    }

    /**
     * @return string
     */
    protected function getTableBinding(): string
    {
        $fromBinding = $this->getBinding('from');

        $table = null;

        array_walk_recursive($fromBinding, function ($item) use (&$table) {
            $table = $item;
        });

        return $table;
    }

    /**
     * @param string $bindingName
     * @param array $binding
     */
    protected function replaceBind(string $bindingName, array $binding): void
    {
        $this->bindings[$bindingName] = $binding;
    }

    /**
     * @param string $bindingName
     */
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

    /**
     * @param string $bindingName
     * @param string $bindingNewName
     * @throws \Exception
     */
    protected function renameBinding(string $bindingName, string $bindingNewName): void
    {
        if (!array_key_exists($bindingName, $this->bindings)) {
            throw new Exception('Binding called "' . $bindingName . '" doesnt exist.');
        }

        $keys = array_keys($this->bindings);

        $keys[array_search($bindingName, $keys)] = $bindingNewName;

        $this->bindings = array_combine($keys, $this->bindings);
    }

    /**
     * @param string $bindingName
     * @param array $binding
     */
    protected function bind(string $bindingName, array $binding): void
    {
        $this->bindings[$bindingName][] = $binding;
    }

    /**
     * @param string $conditionType
     * @param string $whereLogicalType
     * @param string $column
     * @param string $operator
     * @param string $value
     */
    protected function bindInWhereBeforeCheckingForThePresenceOfJson(string $conditionType,
                                                                     string $whereLogicalType,
                                                                     string $column,
                                                                     string $operator,
                                                                     string $value): void
    {
        if (stristr($column, '->')) {
            $fields = explode('->', $column);

            $column = $fields[0];

            unset($fields[0]);

            $expression = match ($this->getDriver()) {
                AvailableDbmsDrivers::MARIADB,
                AvailableDbmsDrivers::MYSQL => 'JSON_UNQUOTE' . $this->concludeBrackets(
                        'JSON_EXTRACT'
                        . $this->concludeBrackets(
                            $this->wrapColumnInPita($column)
                            . ', '
                            . $this->wrapStringInPita(
                                '$.'
                                . implode('.', $this->concludeDoubleQuotes($fields))
                            )
                        )
                    ),
                AvailableDbmsDrivers::POSTGRESQL => $this->wrapColumnInPita($column)
                    . '->'
                    . implode('->>', $this->wrapStringInPita($fields)),
                AvailableDbmsDrivers::ORACLE,
                AvailableDbmsDrivers::MS_SQL_SERVER => 'JSON_VALUE'
                    . $this->concludeBrackets(
                        $this->wrapColumnInPita($column)
                        . ', '
                        . $this->wrapStringInPita(
                            '$.'
                            . implode('.', $this->wrapColumnInPita($fields))
                        )
                    ),
                // Sqlite > 3.38.0
                AvailableDbmsDrivers::SQLITE => 'JSON_EXTRACT' . $this->concludeBrackets(
                        $this->wrapColumnInPita($column)
                        . ', '
                        . $this->wrapStringInPita(
                            '$.'
                            . implode('.', $this->concludeDoubleQuotes($fields))
                        )
                    )
            };
        } else {
            $expression = $this->wrapColumnInPita($column);
        }

        $this->bind($conditionType, [
            $whereLogicalType,
            $expression,
            $operator,
            $value
        ]);
    }

    protected function changeQueryTypeToInsert(): void
    {
        $this->changeQueryType('insert');
    }

    protected function changeQueryTypeToUpdate(): void
    {
        $this->changeQueryType('update', false);
    }

    protected function changeQueryTypeToDelete(): void
    {
        $this->changeQueryType('delete', false, true);
    }

    protected function changeQueryTypeToTruncate(): void
    {
        $this->changeQueryType('truncate', false, false, true);
    }

    /**
     * @param string $bindingName
     * @param bool $useInto
     * @param bool $useFrom
     * @param bool $useTable
     */
    protected function changeQueryType(string $bindingName,
                                       bool $useInto = true,
                                       bool $useFrom = false,
                                       bool $useTable = false): void
    {
        $table = $this->getBinding('from');

        $this->bindings = [$bindingName => $table];

        if ($useFrom) {
            array_unshift($this->bindings[$bindingName], 'FROM');
        }

        if ($useInto) {
            array_unshift($this->bindings[$bindingName], 'INTO');
        }

        if ($useTable) {
            array_unshift($this->bindings[$bindingName], 'TABLE');
        }
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function wrapColumnInPita(string|array $subject): string|array
    {
        $pitaForColumns = $this->driver->getPitaForColumns();

        return $this->concludeEntities($subject, $pitaForColumns['opening'], $pitaForColumns['closing']);
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function wrapStringInPita(string|array $subject): string|array
    {
        $pitaForStrings = $this->driver->getPitaForStrings();

        return $this->concludeEntities($subject, $pitaForStrings['opening'], $pitaForStrings['closing']);
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeSingleQuotes(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, "'");
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeDoubleQuotes(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '"');
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeGraveAccent(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '`');
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeBrackets(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '(', ')');
    }

    /**
     * @param string|array $subject
     * @param string $openSymbol
     * @param string|null $closingSymbol
     * @return string|array
     */
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

    private function useAdditionalAccessories(): void
    {
        $additionalAccessories = $this->driver->getAdditionalAccessories();

        if (!empty($additionalAccessories)) {
            foreach ($additionalAccessories as $accessoryName => $accessory) {
                $this->$accessoryName = array_merge($this->$accessoryName, $accessory);
            }
        }
    }

    /**
     * @param string $column
     * @param bool $forUpdate
     * @return array
     */
    protected function divideSubsequenceFromSequence(string $column, bool $forUpdate = false): array
    {
        $sequence = explode('->', $column);

        $column = $sequence[0];

        unset($sequence[0]);

        if (count($sequence) >= 1) {
            $subsequence = match ($this->getDriver()) {
                AvailableDbmsDrivers::POSTGRESQL => !$forUpdate
                    ? '->' . implode('->', $this->wrapStringInPita($sequence))
                    : ', ' . $this->wrapStringInPita(
                        '{' . implode(', ', $this->wrapColumnInPita($sequence)) . '}'
                    ),
                default => ', ' . $this->wrapStringInPita(
                        '$.' . implode('.', $this->concludeDoubleQuotes($sequence))
                    )
            };
        } else {
            $subsequence = '';
        }

        return compact('subsequence', 'column');
    }

    /**
     * @param string $direction
     * @return string
     */
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

    /**
     * @param string $bindingName
     * @param string $whereLogicalType
     * @param callable $callback
     */
    protected function runCallback(string $bindingName, string $whereLogicalType, callable $callback): void
    {
        $this->bind($bindingName, [$whereLogicalType]);

        $this->bind($bindingName, ['(']);

        $callback($this);

        $this->bind($bindingName, [')']);
    }

    /**
     * @param string $bindingName
     * @param callable $callback
     */
    protected function runCallbackForVirginInstance(string $bindingName, callable $callback): void
    {
        $this->bind($bindingName, ['(']);

        $virginInstance = new $this($this->connection);

        $callback($virginInstance);

        $this->bind($bindingName, [$virginInstance]);

        $this->bind($bindingName, [')']);
    }

    /**
     * @param string|int|float|array $suspect
     * @param array $dataFromWhichToCheck
     * @return bool
     */
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

    /**
     * @param array $array
     * @return bool
     */
    protected function isAssociative(array $array): bool
    {
        $supposedKeys = range(0, count($array) - 1);

        return array_keys($array) !== $supposedKeys;
    }

    /**
     * @param mixed $suspect
     * @throws \Exception
     */
    protected function throwExceptionIfMisplacedArray(mixed $suspect): void
    {
        if (is_array($suspect)) {
            throw new Exception('Invalid value specified. The value cannot be an array.');
        }
    }

    /**
     * @param array $array
     * @param string|null $message
     * @throws \Exception
     */
    protected function throwExceptionIfArrayAssociative(array $array, string|null $message = null): void
    {
        if ($this->isAssociative($array)) {
            throw new Exception(
                is_null($message) ? 'Array cannot be associative.' : $message
            );
        }
    }

    /**
     * @param array $array
     * @param string|null $message
     * @throws \Exception
     */
    protected function throwExceptionIfArrayIsNotAssociative(array $array, string|null $message = null): void
    {
        if (!$this->isAssociative($array)) {
            throw new Exception(
                is_null($message) ? 'Array must be associative.' : $message
            );
        }
    }

    /**
     * @param string $operator
     * @throws \Exception
     */
    protected function throwExceptionIfOperatorIsInvalid(string $operator): void
    {
        if (!$this->checkMatching($operator, $this->operators)) {
            throw new Exception(
                '"' . $operator . '" is not a SQL operator.'
            );
        }
    }

    /**
     * @param string|array $direction
     * @throws \Exception
     */
    protected function throwExceptionIfDirectionIsInvalid(string|array $direction): void
    {
        if (!$this->checkMatching($direction, $this->orderDirections)) {
            throw new Exception(
                'Direction values for "order by" expression are not valid.'
            );
        }
    }

    /**
     * @param string $modifier
     * @throws \Exception
     */
    protected function throwExceptionIfFtsModifierIsInvalid(string $modifier): void
    {
        $reflectionClass = new ReflectionClass(FullTextSearchModifiers::class);

        if (!$this->checkMatching($modifier, $reflectionClass->getConstants())) {
            throw new Exception(
                '"' . $modifier . '" is not a modifier.'
            );
        }
    }

    /**
     * @param mixed $suspect
     * @throws \Exception
     */
    protected function throwExceptionIfArgumentNotNumeric(mixed $suspect): void
    {
        if (!is_numeric($suspect)) {
            throw new Exception('"' . $suspect . '" is not a number or a numeric string.');
        }
    }

    /**
     * @throws \Exception
     */
    protected function throwExceptionIfDriverNotSupportFunction(): void
    {
        throw new Exception('DriverInterface ' . $this->getDriver() . ' does not support this function.');
    }
}