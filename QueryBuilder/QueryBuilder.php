<?php

namespace Moarai\QueryBuilder;

use Exception;
use Moarai\Drivers\AvailableDbmsDrivers;
use Moarai\Drivers\PostgreSqlDriver;

class QueryBuilder
{
    use ClauseBindersToolkit;

    protected $driver;

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

    public function __construct()
    {
        $this->driver = new PostgreSqlDriver();
    }

    public function getDriver(): string
    {
        return $this->driver->getDriverName();
    }

    protected function selectClauseBinder(bool $distinct = false, array|string ...$columns): void
    {
        $flattenedColumns = $this->wrapColumnInPita($columns);

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
        $this->bind('from', [$this->wrapColumnInPita($table)]);
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
                        $this->wrapColumnInPita($column),
                        '=',
                        $value
                    ]);
                } elseif (count($column) > 1) {
                    foreach ($column as $columnName => $columnValue) {
                        $this->throwExceptionIfMisplacedArray($columnValue);

                        if ($columnName === $columnFirstElementKey) {
                            $this->bind($conditionType, [
                                $whereLogicalType,
                                $this->wrapColumnInPita($columnName),
                                '=',
                                $columnValue
                            ]);
                        } else {
                            $this->bind($conditionType, [
                                'AND',
                                $this->wrapColumnInPita($columnName),
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

                    $this->throwExceptionIfOperatorIsInvalid($column[1]);

                    $this->bind($conditionType, [
                        $whereLogicalType,
                        $this->wrapColumnInPita($column[0]),
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
                $this->throwExceptionIfOperatorIsInvalid($operator);
            }

            $this->bind($conditionType, [
                $whereLogicalType,
                $this->wrapColumnInPita($column),
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
                $this->wrapColumnInPita($column),
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

            $this->throwExceptionIfOperatorIsInvalid($operator);
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

            $this->throwExceptionIfOperatorIsInvalid($operator);
        }

        $this->bind('where', [
            $whereLogicalType,
            $isNotCondition ? 'NOT' : '',
            $this->wrapColumnInPita($firstColumn),
            $operator,
            $this->wrapColumnInPita($secondColumn),
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
    /*
     * select col1, col2, ts_rank(col, plain_tsquery('language', 'desired text')) as `rank` from `table` where col
     * @@ plain_tsquery('language', 'desired text') order by rank desc   -> By relevance
     *
     * select title from table where to_tsvector('language', col1) || to_tsvector('language', col2) @@ to_tsquery('language', 'text');
     *
     * TODO mark word in result text
     */
    protected function whereFullTextClauseBinder(string $whereLogicalType,
                                                 string|array $column,
                                                 string $value,
                                                 string $searchModifier,
                                                 string|array|null $rankingColumn,
                                                 string|int|array $normalizationBitmask,
                                                 bool $highlighting,
                                                 bool $isNotCondition = false): void
    {
        switch ($this->getDriver()) {
            case AvailableDbmsDrivers::MYSQL:
                $column = $this->concludeBrackets(implode(', ', $this->wrapColumnInPita($column)));

                $value = $this->wrapStringInPita($value);

                $this->throwExceptionIfFtsModifierIsInvalid($searchModifier);

                $value .= ' ' . $searchModifier;

                $this->bind('where', [
                    $whereLogicalType,
                    $isNotCondition ? 'NOT' : '',
                    'MATCH',
                    $column,
                    'AGAINST',
                    $this->concludeBrackets($value)
                ]);

                break;
            case AvailableDbmsDrivers::POSTGRESQL:
                $valueOpenExpression = 'to_tsquery(';

                $vectorOpenExpression = 'to_tsvector(';

                if (!empty($searchModifier)) {
                    $valueOpenExpression .= $this->wrapStringInPita($searchModifier) . ', ';

                    $vectorOpenExpression .= $this->wrapStringInPita($searchModifier) . ', ';
                }

                if (!is_array($column)) {
                    $column = [$column];
                }

                $weighing = $this->isAssociative($column);

                if ($weighing) {
                    $weights = $column;

                    array_walk($weights, function ($value, $key) use ($weights) {
                        $weights[$key] = strtoupper($value);
                    });

                    if (!$this->checkMatching($weights, $this->driver->getWeights())) {
                        throw new Exception(
                            'Wrong weight argument. The weight argument must be one of the following elements.
                            "A", "B", "C", "D". Or the same letters in lower case translation.'
                        );
                    }

                    $column = array_keys($column);
                }

                if ($highlighting) {
                    $glowwormOpenExpression = 'ts_headline(';

                    if (!empty($searchModifier)) {
                        $glowwormOpenExpression .= $this->wrapStringInPita($searchModifier) . ', ';
                    }

                    $glowworms = [];

                    foreach ($column as $item) {
                        $glowworms[] = $this->concludeEntities(
                            $this->wrapColumnInPita($item) . ', ',
                            $glowwormOpenExpression,
                            $valueOpenExpression
                            . $this->wrapStringInPita($value)
                            . '))'
                        );
                    }

                    $glowworms = implode(', ', $glowworms);


                    $this->bind('select', [$glowworms]);
                }

                if (!is_null($rankingColumn)) {
                    if (!$this->checkMatching($rankingColumn, $column)) {
                        throw new Exception(
                            'The ranking column must be one of the columns listed in the first argument.'
                        );
                    }

                    if (empty($normalizationBitmask)) {
                        $normalizationBitmask = 32;
                    } else {
                        if (!$this->checkMatching($normalizationBitmask, $this->driver->getNormalizationBitmasks())) {
                            throw new Exception(
                                'The bitmask can be one of the following values "0, 1, 2, 4, 8, 16, 32". 
                            Multiple masks can be used by passing the masks as an array.'
                            );
                        }
                    }

                    if (is_array($normalizationBitmask)) {
                        $normalizationBitmask = implode('|', $normalizationBitmask);
                    }

                    if (!is_array($rankingColumn)) {
                        $rankingColumn = [$rankingColumn];
                    }

                    $relevancyColumns = [];

                    $columnsForRankingByRelevance = [];

                    foreach ($rankingColumn as $key => $item) {
                        $itemName = $item;

                        $relevancyColumn = 'rank_' . $item;

                        $relevancyColumns[] = $relevancyColumn;

                        if ($weighing) {
                            $item = $this->concludeEntities(
                                $this->wrapColumnInPita($item),
                                'setweight(' . $valueOpenExpression,
                                '), ' . $this->wrapStringInPita($weights[$itemName]) . ')'
                            );
                        } else {
                            $item = $this->concludeEntities(
                                $this->wrapColumnInPita($item),
                                $vectorOpenExpression,
                                ')'
                            );
                        }

                        $columnsForRankingByRelevance[] = $this->concludeEntities(
                            $item,
                            'ts_rank_cd(',
                            ', '
                            . $valueOpenExpression
                            . $this->wrapStringInPita($value)
                            . '), ' . $normalizationBitmask . ')'
                            . ' AS ' . $this->wrapColumnInPita($relevancyColumn)
                        );
                    }

                    $columnsForRankingByRelevance = implode(', ', $columnsForRankingByRelevance);

                    $this->bind('select', [
                        $columnsForRankingByRelevance
                    ]);

                    $this->bind('orderBy', [
                        implode(', ', $this->wrapColumnInPita($relevancyColumns)) . ' DESC'
                    ]);
                }

                $tsVectors = $this->concludeEntities(
                    $this->wrapColumnInPita($column),
                    $vectorOpenExpression,
                    ')'
                );

                $value = $this->concludeEntities(
                    $this->wrapStringInPita($value),
                    $valueOpenExpression,
                    ')'
                );

                if (is_array($tsVectors)) {
                    $tsVectors = implode(' || ', $tsVectors);
                }

                $this->bind('where', [
                    $whereLogicalType,
                    $tsVectors,
                    '@@',
                    $value
                ]);

                break;
        }
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
                $this->wrapStringInPita($column),
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
                $this->wrapStringInPita($column),
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
            $this->throwExceptionIfDirectionIsInvalid($direction);

            if (is_array($column)) {
                $this->throwExceptionIfArrayAssociative($column);
            }

            $column = $this->wrapStringInPita($column);
        }

        $this->bind('orderBy', [
            !$inRandomOrder ? $column : '',
            !$inRandomOrder ? $direction : 'RAND' . $this->concludeBrackets($column)
        ]);
    }

    protected function groupByClauseBinder(string|array ...$columns)
    {
        $flattenedColumns = $this->wrapStringInPita($columns);

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

    // ODKU -> on duplicate key update
    // ODKU available ^ PostgreSQL 9.5.
    // ODKU available ^ SQLite 3.24.0 (04.06.2018)
    // <-- SqLite (PostgreSQL, MySQL ?) - UPSERT не вмешивается в случае сбоя NOT NULL или ограничений
    // внешнего ключа или ограничений, реализованных с помощью триггеров.
    // В настоящее время UPSERT не работает с виртуальными столами. -->

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
                    $readyUpdate .= ' ' . $this->wrapStringInPita($item);

                    $readyUpdate .= match ($this->getDriver()) {
                        AvailableDbmsDrivers::MYSQL => ' = VALUES' . $this->concludeBrackets(
                                $this->wrapStringInPita($item)
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
                                    $this->wrapStringInPita(
                                        $uniqueBy ? $uniqueBy : ''
                                    )
                                )
                            );
                        } elseif (is_string($uniqueBy)) {
                            $odkuPostfix .= $this->concludeBrackets(
                                $this->wrapStringInPita($uniqueBy)
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

                $columns = $this->wrapStringInPita(
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
                    case AvailableDbmsDrivers::POSTGRESQL:
                        $this->bind('insert', ['ON CONFLICT DO NOTHING']);

                        break;
                }
            }
        } else {
            $this->throwExceptionIfArrayAssociative($columnsWithValues);

            $columnsWithValues = $this->wrapStringInPita($columnsWithValues);

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
                    if (!strpbrk($binding, '()`\'"')) {
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
