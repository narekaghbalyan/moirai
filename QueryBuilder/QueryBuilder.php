<?php

namespace Moarai\QueryBuilder;

use Exception;
use Moarai\Drivers\AvailableDbmsDrivers;

class QueryBuilder
{
    use ClauseBindersToolkit;

    protected string $driver = AvailableDbmsDrivers::POSTGRESQL;

    protected array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'orderBy' => [],
        'union' => [],
        'unionOrder' => [],
        'limit' => [],
        'offset' => []
    ];

    public function getDriver()
    {
        return $this->driver;
    }

    protected function selectClauseBinder(bool $distinct = false, array|string ...$columns): void
    {
        $flattenedColumns = $this->concludeGraveAccent($columns);

        if (empty($flattenedColumns)) {
            $flattenedColumns = '*';
        }

        $this->bind('select', [
            $distinct ? 'DISTINCT' : '',
            $flattenedColumns
        ]);
    }

    protected function fromClauseBinder(string $table): void
    {
        $this->bind('from', [$this->concludeGraveAccent($table)]);
    }

    /*
     * where('column', '=', 'value') -> where column = value
     * where(['column', '=', 'value']) -> where column = value
     * where(['column' => 'value']) -> where column = value
     * where(['column1' => 'value1', 'column2' => 'value2']) -> where column1 = value1 and column2 = value2
     * where(function ($query) { $query->... }, '=', value) -> where $query->result = value
     */
    protected function baseConditionClauseBinder(string $whereLogicalType,
                                                 string $conditionType,
                                                 string|array|callable $column,
                                                 string|null $operator,
                                                 string $value): void
    {
        if (is_array($column)) {
            if (!is_null($operator) || !empty($value)) {
                throw new Exception(
                    'If for the "' . $conditionType . '" function the first argument is passed as an array, 
                    then the following arguments must be omitted.'
                );
            }

            if ($this->isAssociative($column)) {
                $keys = array_keys($column);
                $columnFirstElementKey = $keys[0];

                if (count($column) === 1) {
                    $value = array_pop($column);

                    $column = $columnFirstElementKey;

                    $this->throwExceptionIfMisplacedArray($column);

                    $this->bind($conditionType, [
                        $whereLogicalType,
                        $this->concludeGraveAccent($column),
                        '=',
                        $value
                    ]);
                } elseif (count($column) > 1) {
                    foreach ($column as $columnName => $columnValue) {
                        $this->throwExceptionIfMisplacedArray($columnValue);

                        if ($columnName === $columnFirstElementKey) {
                            $this->bind($conditionType, [
                                $whereLogicalType,
                                $this->concludeGraveAccent($columnName),
                                '=',
                                $columnValue
                            ]);
                        } else {
                            $this->bind($conditionType, [
                                'AND',
                                $this->concludeGraveAccent($columnName),
                                '=',
                                $columnValue
                            ]);
                        }
                    }
                }
            } else {
                if (count($column) === 3) {
                    foreach ($column as $columnValue) {
                        $this->throwExceptionIfMisplacedArray($columnValue);
                    }

                    $this->checkOperatorMatching($column[1]);

                    $this->bind($conditionType, [
                        $whereLogicalType,
                        $this->concludeGraveAccent($column[0]),
                        $column[1],
                        $column[2]
                    ]);
                } else {
                    throw new Exception(
                        'Invalid data for "' . $conditionType . '" clause.'
                    );
                }
            }
        } elseif (is_string($column)) {
            if (empty($value)) {
                if (!$this->checkMatching($operator, $this->operators)) {
                    $value = $operator;
                    $operator = '=';
                } else {
                    throw new Exception(
                        'Missing argument in "' . $conditionType . '" function.'
                    );
                }
            } else {
                $this->checkOperatorMatching($operator);
            }

            $this->bind($conditionType, [
                $whereLogicalType,
                $this->concludeGraveAccent($column),
                $operator,
                $value
            ]);
        } elseif (is_callable($column)) {
            if (is_null($operator) || empty($value)) {
                throw new Exception(
                    'Missing argument in "' . $conditionType . '" function.'
                );
            }

            $this->bind($conditionType, [$whereLogicalType]);

            $this->runCallbackForVirginInstance($conditionType, $column);

            $this->bind($conditionType, [
                $operator,
                $value
            ]);
        }
    }

    protected function whereBetweenClauseBinder(string $whereLogicalType,
                                                string|callable $column,
                                                array|string|int|float $range,
                                                string|int|float $endOfRange,
                                                bool $isNotCondition = false,
                                                bool $betweenColumns = false): void
    {
        if (!is_callable($column)) {
            if (is_array($range)) {
                $this->throwExceptionIfArrayAssociative(
                    $range,
                    'Array for range cannot be associative.'
                );

                if (count($range) !== 2) {
                    throw new Exception(
                        'Array for range must contain 2 elements. 
                        The first value is the beginning of the range, the second value is the end of the range.'
                    );
                }

                if (!empty($endOfRange)) {
                    throw new Exception(
                        'If the range is specified as an array, then the third argument 
                        of the function must be skipped.'
                    );
                }

                if ($betweenColumns) {
                    $startOfRange = $this->concludeBrackets('SELECT ' . $range[0]);
                    $endOfRange = $this->concludeBrackets('SELECT ' . $range[1]);
                } else {
                    $startOfRange = $range[0];
                    $endOfRange = $range[1];
                }
            } else {
                if (empty($endOfRange)) {
                    throw new Exception('Range end not specified');
                }

                $startOfRange = $range;
            }

            $this->bind('where', [
                $whereLogicalType,
                $this->concludeGraveAccent($column),
                $isNotCondition ? 'NOT' : '',
                'BETWEEN',
                $startOfRange . ' AND ' . $endOfRange
            ]);
        } else {
            $this->runCallback(
                'where',
                $whereLogicalType,
                $column
            );
        }
    }

    protected function whereColumnClauseBinder(string $whereLogicalType,
                                               string|array $firstColumn,
                                               string|null $operator,
                                               string|null $secondColumn,
                                               bool $isNotCondition = false): void
    {
        if (is_string($firstColumn)) {
            if (empty($operator) || empty($secondColumn)) {
                throw new Exception(
                    'Missing argument in where column function.'
                );
            }

            $this->checkOperatorMatching($operator);
        } elseif (is_array($firstColumn)) {
            if (!empty($operator) || !empty($secondColumn)) {
                throw new Exception(
                    'If the first argument is an array, then the following arguments must be omitted.'
                );
            }

            if (count($firstColumn) !== 3) {
                throw new Exception('Array must contain 3 elements.');
            }

            if ($this->isAssociative($firstColumn)) {
                throw new Exception('Array cannot be associative.');
            }

            $columns = $firstColumn;

            $firstColumn = $columns[0];
            $operator = $columns[1];
            $secondColumn = $columns[2];

            $this->checkOperatorMatching($operator);
        }

        $this->bind('where', [
            $whereLogicalType,
            $isNotCondition ? 'NOT' : '',
            $this->concludeGraveAccent($firstColumn),
            $operator,
            $this->concludeGraveAccent($secondColumn),
        ]);
    }

    protected function whereExistsClauseBinder(string $whereLogicalType,
                                               callable $callback,
                                               bool $isNotCondition = false)
    {
        $this->bind('where', [
            $whereLogicalType,
            $isNotCondition ? 'NOT' : '',
            'EXISTS'
        ]);

        $this->runCallbackForVirginInstance(
            'where',
            $callback
        );
    }

    /*
     * 'against (',
     * $value,
     * 'in natural language mode)'
     */
    protected function whereFullTextClauseBinder(string $whereLogicalType,
                                                 string $column,
                                                 string $value,
                                                 bool $isNotCondition = false): void
    {
        $this->bind('where', [
            $whereLogicalType,
            $isNotCondition ? 'NOT' : '',
            'MATCH',
            $this->concludeGraveAccent($column),
            'against',
            $this->concludeBrackets($value . 'in natural language mode')
        ]);
    }

    protected function whereInClauseBinder(string $whereLogicalType,
                                           string|callable $column,
                                           array $setOfSupposedVariables,
                                           bool $isNotCondition = false): void
    {
        if (!is_callable($column)) {
            $this->throwExceptionIfArrayAssociative(
                $setOfSupposedVariables,
                'Array for variables cannot be associative'
            );

            if (empty($setOfSupposedVariables)) {
                throw new Exception('Array for values cannot be empty');
            }

            $this->bind('where', [
                $whereLogicalType,
                $this->concludeGraveAccent($column),
                $isNotCondition ? 'NOT' : '',
                'IN',
                $this->concludeBrackets(implode(', ', $setOfSupposedVariables))
            ]);
        } else {
            $this->runCallback(
                'where',
                $whereLogicalType,
                $column
            );
        }
    }

    protected function whereNullClauseBinder(string $whereLogicalType,
                                             string|callable $column,
                                             bool $isNotCondition = false)
    {
        if (!is_callable($column)) {
            $this->bind('where', [
                $whereLogicalType,
                $this->concludeGraveAccent($column),
                'IS',
                $isNotCondition ? 'NOT' : '',
                'null'
            ]);
        } else {
            $this->runCallback(
                'where',
                $whereLogicalType,
                $column
            );
        }
    }

    protected function orderByClauseBinder(string|array $column, string $direction, bool $inRandomOrder = false)
    {
        if (!$inRandomOrder) {
            $this->checkDirectionMatching($direction);

            if (is_array($column)) {
                $this->throwExceptionIfArrayAssociative($column);
            }

            $column = $this->concludeGraveAccent($column);
        }

        $this->bind('orderBy', [
            !$inRandomOrder ? $column : '',
            !$inRandomOrder ? $direction : 'RAND' . $this->concludeBrackets($column)
        ]);
    }

    protected function groupByClauseBinder(string|array ...$columns)
    {
        $flattenedColumns = $this->concludeGraveAccent($columns);

        $this->bind('groupBy', [
            $flattenedColumns
        ]);
    }

    protected function offsetClauseBinder(int $count)
    {
        $this->bind('offset', [$count]);
    }

    protected function limitClauseBinder(int $count)
    {
        $this->bind('limit', [$count]);
    }

    // TODO
    protected function whenClauseBinder(bool $value, callable $callback, callable|null $else)
    {
        if ($value) {
            $callback($this);
        } elseif (!is_null($else)) {
            $else($this);
        }
    }

    protected function getClause()
    {
        $this->executeQuery($this->pickUpThePieces($this->bindings));
    }

    // odku available ^ PostgreSQL 9.5.
    // odku -> on duplicate key update
    protected function insertClauseBinder(array $columnsWithValues,
                                          mixed $query = null,
                                          bool $ignore = false,
                                          bool $odku = false,
                                          string|array|null $uniqueBy = null,
                                          string|array|null $update = null)
    {
        if (empty($columnsWithValues)) {
            throw new Exception('Array cannot be empty.');
        }

        $firstElement = $columnsWithValues[array_key_first($columnsWithValues)];

        $columnsWithValues = is_array($firstElement) && is_array(
            $firstElement[array_key_first($firstElement)]
        ) ? $firstElement : $columnsWithValues;

        if (is_null($query)) {
            $this->changeQueryTypeToInsert('insert');

            if ($odku) {
                if (!is_array($columnsWithValues[array_key_first($columnsWithValues)])) {
                    $columnsWithValues = [$columnsWithValues];
                }

                if (is_null($update)) {
                    $update = $columnsWithValues[0];

                    $update = array_values(array_flip($update));
                }

                $lastKey = array_key_last($update);

                $readyUpdate = '';

                foreach ($update as $key => $item) {
                    $readyUpdate .= ' ' . $this->concludeGraveAccent($item);

                    $readyUpdate .= match ($this->getDriver()) {
                        AvailableDbmsDrivers::MYSQL => ' = VALUES' . $this->concludeBrackets(
                                $this->concludeGraveAccent($item)
                            ),
                        AvailableDbmsDrivers::POSTGRESQL => ' = EXCLUDED.' . $item,
                    };

                    $key !== $lastKey ? $readyUpdate .= ',' : $readyUpdate .= '';
                }

                switch ($this->getDriver()) {
                    case AvailableDbmsDrivers::MYSQL:
                        $odkuPostfix = 'ON DUPLICATE KEY UPDATE' . $readyUpdate;

                        break;
                    case AvailableDbmsDrivers::POSTGRESQL:
                        $odkuPostfix = 'ON CONFLICT ';

                        if (empty($uniqueBy)) {
                            throw new Exception(
                                'When using "on conflict" command in postgreSQL, those fields that are 
                                        unique must be filled in the "upsert" method.'
                            );

                            // TODO put away exception
                        } elseif (is_array($uniqueBy)) {
                            $odkuPostfix .= $this->concludeBrackets(
                                implode(
                                    ', ',
                                    $this->concludeGraveAccent(
                                        $uniqueBy ? $uniqueBy : ''
                                    )
                                )
                            );
                        } elseif (is_string($uniqueBy)) {
                            $odkuPostfix .= $this->concludeBrackets(
                                $this->concludeGraveAccent($uniqueBy)
                            );
                        }

                        $odkuPostfix .= ' DO UPDATE SET ' . $readyUpdate;

                        break;
                }
            }

            $usedColumns = [];

            $columnsAlreadyReserved = false;

            $odkuStatementReadyForInsertion = false;

            foreach ($columnsWithValues as $key => $columnWithValue) {
                $this->throwExceptionIfArrayIsNotAssociative($columnWithValue);

                $columns = $this->concludeGraveAccent(
                    array_keys($columnWithValue)
                );

                $usedColumns[] = $columns;

                if (count(array_unique($usedColumns, SORT_REGULAR)) !== 1) {
                    throw new Exception('Columns in arrays do not match');
                }

                $values = array_values($columnWithValue);

                if ($key === array_key_last($columnsWithValues)) {
                    $odkuStatementReadyForInsertion = true;
                }

                $this->bind('insert', [
                    !$columnsAlreadyReserved ? $this->concludeBrackets(implode(', ', $columns)) : '',
                    !$columnsAlreadyReserved ? 'VALUES' : '',
                    $this->concludeBrackets(implode(', ', $values)),
                    $odku ? (
                    $odkuStatementReadyForInsertion ? $odkuPostfix : ''
                    ) : '',
                ]);

                $columnsAlreadyReserved = true;
            }

            if ($ignore) {
                switch ($this->getDriver()) {
                    case AvailableDbmsDrivers::MYSQL:
                        array_unshift($this->bindings['insert'], 'IGNORE');

                        break;
                    case AvailableDbmsDrivers::POSTGRESQL;
                        $this->bind('insert', ['ON CONFLICT DO NOTHING']);

                        break;
                }
            }
        } else {
            $this->throwExceptionIfArrayAssociative($columnsWithValues);

            $columnsWithValues = $this->concludeGraveAccent($columnsWithValues);

            $tables = $this->getBinding('from');

            if (count($tables) > 1) {
                $query->replaceBind('from', $tables[array_key_last($tables)]);
            }

            if (!is_object($query) || !method_exists($query, 'getBindings')) {
                throw new Exception('The second argument must be a query.');
            }

            $subQueryBindings = $query->getBindings();

            $this->changeQueryTypeToInsert('insert');

            $this->replaceBind('insert', ['INTO', $tables[array_key_first($tables)]]);

            $this->bind('insert', [
                $this->concludeBrackets(implode(', ', $columnsWithValues)),
                $subQueryBindings
            ]);
        }

        return $this->executeQuery($this->pickUpThePieces($this->bindings));
    }

    private function pickUpThePieces(array $bindings): string
    {
        $query = '';

        foreach ($bindings as $bindingName => $binding) {
            if (!empty($binding)) {
                if (is_string($bindingName)) {
                    $query .= strtoupper($bindingName);
                }

                if (is_array($binding)) {
                    $query .= ' ' . $this->pickUpThePieces($binding) . ' ';
                } else {
                    if (!strpbrk($binding, '()`')) {
                        $binding = strtoupper($binding);
                    }

                    $query .= ' ' . $binding . ' ';
                }
            }
        }

        return trim(
            preg_replace('/\s+/', ' ', $query)
        );
    }

    private function executeQuery(string $statement)
    {
        dd($statement);

        return 0;
    }
}
