<?php

namespace Moarai\QueryBuilder;

use Exception;
use Moarai\Drivers\AvailableDbmsDrivers;
use Moarai\Drivers\MariaDbDriver;
use Moarai\Drivers\MsSqlServerDriver;
use Moarai\Drivers\MySqlDriver;
use Moarai\Drivers\OracleDriver;
use Moarai\Drivers\PostgreSqlDriver;
use Moarai\Drivers\SqliteDriver;

class QueryBuilder
{
    use ClauseBindersToolkit;

    protected $driver;

    protected array $bindings = [
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

    public function __construct()
    {
        $this->driver = new OracleDriver();

        $this->useAdditionalAccessories();
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

    protected function whereFullTextClauseBinder(string $whereLogicalType,
                                                 string|array $column,
                                                 string $value,
                                                 string $searchModifier,
                                                 string|array|null $rankingColumn,
                                                 string|int|array $normalizationBitmask,
                                                 bool|array $highlighting,
                                                 bool $isNotCondition = false): void
    {
        switch ($this->getDriver()) {
            case AvailableDbmsDrivers::MARIADB:
            case AvailableDbmsDrivers::MYSQL:
                if (!is_array($column)) {
                    $column = [$column];
                }

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

                    $argumentsExists = false;

                    if (is_array($highlighting)) {
                        $argumentsExists = true;

                        if (
                        !$this->checkMatching(array_keys($highlighting), $this->driver->getHighlightingArguments())
                        ) {
                            throw new Exception(
                                'Arguments to the highlighting function can be as follows "'
                                . implode(', ', $this->driver->getHighlightingArguments()) . '".'
                            );
                        }

                        $highlightingArguments = [];

                        foreach ($highlighting as $argumentName => $argumentValue) {
                            if ($argumentName === 'Tag') {
                                $highlightingArguments[] = 'StartSel=' . htmlspecialchars(
                                        $this->concludeEntities($argumentValue, '<', '>')
                                    );

                                $highlightingArguments[] = 'StopSel=' . htmlspecialchars(
                                        $this->concludeEntities($argumentValue, '</', '>')
                                    );
                            } else {
                                $highlightingArguments[] = $argumentName . '=' . $argumentValue;
                            }
                        }


                        $highlightingArguments = implode(', ', $highlightingArguments);
                    }

                    if (!empty($searchModifier)) {
                        $glowwormOpenExpression .= $this->wrapStringInPita($searchModifier) . ', ';
                    }

                    $glowworms = [];

                    foreach ($column as $item) {
                        $glowwormCloseExpression = $valueOpenExpression . $this->wrapStringInPita($value) . ')';

                        if ($argumentsExists) {
                            $glowwormCloseExpression .= ', ' . $this->wrapStringInPita($highlightingArguments);
                        }

                        $glowwormCloseExpression .= ')';

                        $glowworms[] = $this->concludeEntities(
                            $this->wrapColumnInPita($item) . ', ',
                            $glowwormOpenExpression,
                            $glowwormCloseExpression
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
                                'The bitmask can be one of the following values "'
                                . implode(', ', $this->driver->getNormalizationBitmasks())
                                . '". Multiple masks can be used by passing the masks as an array.'
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
            case AvailableDbmsDrivers::SQLITE:
                // TODO create a virtual table with schema creator

                // CREATE VIRTUAL TABLE "virtual_demo9" USING FTS5("name", "hint");
                // INSERT INTO "virtual_demo9" SELECT "name", "hint" FROM "demo";
                // SELECT * FROM "virtual_demo9" WHERE "hint" MATCH 'The most';
                // SELECT * FROM "virtual_demo11" WHERE "virtual_demo11" MATCH 'name:The most OR hint:The most';

                if (!is_array($column)) {
                    $column = [$column];
                }

                $columnsForVirtualTable = implode(', ', $this->wrapColumnInPita($column));

                $table = $this->getTableBinding();

                $virtualTable = $this->wrapColumnInPita('virtual_' . str_replace('"', '', $table));

                $virtualTableDeclare = 'CREATE VIRTUAL TABLE '
                    . $virtualTable
                    . ' USING FTS5'
                    . $this->concludeBrackets($columnsForVirtualTable)
                    . ';';

                $insertingInVirtualTable = 'INSERT INTO '
                    . $virtualTable
                    . ' ' . $this->concludeBrackets($columnsForVirtualTable)
                    . ' SELECT '
                    . $columnsForVirtualTable
                    . ' FROM '
                    . $table
                    . ';';

                array_unshift($this->bindings, $virtualTableDeclare . ' ' . $insertingInVirtualTable);

                if (count($column) > 1) {
                    $columnForMatching = '';

                    array_map(function ($item) use (&$columnForMatching, $value) {
                        $columnForMatching .= $item . ':' . $value . ' ';
                    }, $column);

                    $value = $this->wrapStringInPita(trim($columnForMatching));

                    $column = $virtualTable;
                } else {
                    $value = $this->wrapStringInPita($value);

                    $column = $this->wrapColumnInPita($column);
                }

                $this->replaceBind('from', [
                    $table . ' t INNER JOIN ' . $virtualTable . ' s ON s.id = t.id'
                ]);

                $this->bind('where', [
                    $whereLogicalType, //?
                    $column,
                    $isNotCondition ? 'NOT' : '',
                    'MATCH',
                    $value
                ]);

                break;
            case AvailableDbmsDrivers::MSSQLSERVER:
                if (!is_array($column)) {
                    $column = [$column];
                }

                $table = $this->getTableBinding();

                $rankingExpression = 'FREETEXTTABLE'
                    . $this->concludeBrackets(
                        $table
                        . ', '
                        . $this->wrapColumnInPita($column[0])
                        . ', '
                        . $this->wrapStringInPita($value)
                    )
                    . ' AS ' . $this->wrapColumnInPita('fts_table')
                    . ' INNER JOIN '
                    . $table
                    . ' ON '
                    . $this->wrapColumnInPita('fts_table')
                    . '.'
                    . $this->wrapColumnInPita('key')
                    . ' = '
                    . $table
                    . '.'
                    . $this->wrapColumnInPita('id');

                $this->replaceBind('from', [$rankingExpression]);

                $this->bind('orderBy', [
                    $this->wrapColumnInPita('fts_table')
                    . '.'
                    . $this->wrapColumnInPita('rank')
                    . ' DESC'
                ]);

                break;
            case AvailableDbmsDrivers::ORACLE:
                if (!is_array($column)) {
                    $column = [$column];
                }

                $scores = [];

                $containers = [];

                foreach ($column as $iterator => $item) {
                    $iterator += 1;

                    $containers[] = 'CONTAINS'
                        . $this->concludeBrackets(
                            $this->wrapColumnInPita($item)
                            . ', '
                            . $this->wrapStringInPita($value)
                            . ', '
                            . $iterator
                        )
                        . ' > 0';

                    $scores[] = 'SCORE' . $this->concludeBrackets($iterator);
                }

                $this->bind('select', [implode(', ', $scores)]);

                $this->bind('where', [implode(' OR ', $containers)]);

                $this->bind('orderBy', [implode(' DESC, ', $scores) . ' DESC']);

                // TODO find a better option

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
                $this->wrapColumnInPita($column),
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
                $this->wrapColumnInPita($column),
                'IS',
                $isNotCondition ? 'NOT' : '',
                'NULL'
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
            $needDirection = true;

            $direction = $this->supplementDirection($direction);

            if (empty($column)) {
                throw new Exception('"column" argument cannot be empty.');
            }

            if (is_array($column)) {
                $this->throwExceptionIfDirectionIsInvalid(strtolower($direction));

                $needDirection = false;

                if ($this->isAssociative($column)) {
                    $column = array_map(function ($key, $value) {
                        $this->throwExceptionIfDirectionIsInvalid(strtolower($value));

                        $value = $this->supplementDirection($value);

                        return $this->wrapColumnInPita($key) . ' ' . strtoupper($value);
                    }, array_keys($column), $column);
                } else {
                    $column = array_map(function ($value) use ($direction) {
                        return $this->wrapColumnInPita($value) . ' ' . strtoupper($direction);
                    }, $column);
                }

                $column = implode(', ', $column);
            } else {
                $column = [$column];

                $column = implode(', ', $this->wrapColumnInPita($column));
            }
        } else {
            $randomExpression = match ($this->getDriver()) {
                AvailableDbmsDrivers::MARIADB,
                AvailableDbmsDrivers::MYSQL => 'RAND()',
                AvailableDbmsDrivers::POSTGRESQL,
                AvailableDbmsDrivers::SQLITE => 'RANDOM()'
            };
        }

        $this->bind('orderBy', [
            !$inRandomOrder ? $column : '',
            !$inRandomOrder ? ($needDirection ? $direction : '') : $randomExpression
        ]);
    }

    protected function groupByClauseBinder(string|array ...$columns)
    {
        $this->bind('groupBy', [
            $this->wrapColumnInPita($columns)
        ]);
    }

    protected function offsetClauseBinder(int $count)
    {
        if ($this->getDriver() === AvailableDbmsDrivers::ORACLE) {
            $count .= ' ROWS';
        }

        $this->bind('offset', [$count]);
    }

    protected function limitClauseBinder(int $count, bool $inPercentages)
    {
        if ($this->getDriver() !== AvailableDbmsDrivers::ORACLE) {
            if ($inPercentages) {
                throw new Exception(
                    '"'
                    . $this->getDriver()
                    . '" database management system does not support limit request with percentage applied.'
                );
            }
        } else {
            $count = ' FETCH FIRST ' . $count . ' %s ' . ' ROWS ONLY';

            $count = $inPercentages ? sprintf($count, 'PERCENT') : sprintf($count, '');
        }

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
            $table = $this->getTableBinding();

            $this->changeQueryTypeToInsert();

            if ($odku) {
                if (!is_array($columnsWithValues[array_key_first($columnsWithValues)])) {
                    $columnsWithValues = [$columnsWithValues];
                }

                if (empty($update)) {
                    $update = $columnsWithValues[0];

                    $update = array_values(array_flip($update));
                } elseif (!is_array($update)) {
                    $update = [$update];
                }

                if (!$this->checkMatching(
                    $update, array_keys($columnsWithValues[array_key_first($columnsWithValues)]))
                ) {
                    throw new Exception('The field to update must be contained in the first argument.');
                }

                $lastKey = array_key_last($update);

                $readyUpdate = '';

                foreach ($update as $key => $item) {
                    $readyUpdate .= ' ' . $this->wrapColumnInPita($item);

                    $readyUpdate .= match ($this->getDriver()) {
                        AvailableDbmsDrivers::MSSQLSERVER => ' = ' . $this->wrapStringInPita(
                                $columnsWithValues[array_key_first($columnsWithValues)][$item]
                            ),
                        AvailableDbmsDrivers::MYSQL => ' = VALUES' . $this->concludeBrackets(
                                $this->wrapStringInPita($item)
                            ),
                        AvailableDbmsDrivers::POSTGRESQL,
                        AvailableDbmsDrivers::SQLITE => ' = EXCLUDED.' . $item,
                    };

                    $key !== $lastKey ? $readyUpdate .= ',' : $readyUpdate .= '';
                }

                switch ($this->getDriver()) {
                    case AvailableDbmsDrivers::MARIADB:
                    case AvailableDbmsDrivers::MYSQL:
                        $odkuPostfix = 'ON DUPLICATE KEY UPDATE' . $readyUpdate;

                        break;
                    case AvailableDbmsDrivers::SQLITE:
                    case AvailableDbmsDrivers::POSTGRESQL:
                        $odkuPostfix = 'ON CONFLICT ';

                        if (!empty($uniqueBy)) {
                            if (is_string($uniqueBy)) {
                                $uniqueBy = [$uniqueBy];
                            }

                            $odkuPostfix .= $this->concludeBrackets(
                                implode(', ', $this->wrapColumnInPita($uniqueBy))
                            );
                        }

                        $odkuPostfix .= ' DO UPDATE SET ' . $readyUpdate;

                        break;
                    case AvailableDbmsDrivers::ORACLE:
                    case AvailableDbmsDrivers::MSSQLSERVER;
                        $this->devastateBinding('insert');

                        $odkuPostfix = '';

                        $mergeValues = [];

                        foreach ($columnsWithValues as $columnWithValue) {
                            $mergeValues[] = $this->concludeBrackets(
                                implode(', ', $this->wrapStringInPita($columnWithValue))
                            );
                        }

                        $mergingTable = $this->wrapColumnInPita('moarai_source');

                        $matchingExpression = [];

                        $uniqueExpression = [];

                        if (!empty($uniqueBy)) {
                            if (!is_array($uniqueBy)) {
                                $uniqueBy = [$uniqueBy];
                            }

                            foreach ($uniqueBy as $uniqueColumn) {
                                $uniqueExpression[] = $mergingTable
                                    . '.'
                                    . $this->wrapColumnInPita($uniqueColumn)
                                    . ' = '
                                    . $table
                                    . '.'
                                    . $this->wrapColumnInPita($uniqueColumn);
                            }
                        }

                        foreach ($update as $item) {
                            $matchingExpression[] = $this->wrapColumnInPita($item)
                                . ' = '
                                . $mergingTable
                                . '.'
                                . $this->wrapColumnInPita($item);
                        }

                        $mergeExpression = $table . ' USING '
                            . $this->concludeBrackets(
                                'VALUES ' . implode(', ', $mergeValues)
                            )
                            . ' '
                            . $mergingTable
                            . ' '
                            . $this->concludeBrackets(
                                implode(
                                    ', ',
                                    $this->wrapColumnInPita(
                                        array_keys(
                                            $columnsWithValues[array_key_first($columnsWithValues)]
                                        )
                                    )
                                )
                            )
                            . ' ON '
                            . implode(' AND ', $uniqueExpression)
                            . ' WHEN MATCHED THEN UPDATE SET '
                            . implode(', ', $matchingExpression)
                            . ' WHEN NOT MATCHED THEN';

                        $this->bindings = array_merge([
                            'merge' => $mergeExpression
                        ], $this->bindings);

                        break;
                }
            }

            $usedColumns = [];

            $insertionValues = [];

            $insertionColumns = [];

            foreach ($columnsWithValues as $columnWithValue) {
                $this->throwExceptionIfArrayIsNotAssociative($columnWithValue);

                $columns = $this->concludeBrackets(
                    implode(
                        ', ',
                        $this->wrapColumnInPita(
                            array_keys($columnWithValue)
                        )
                    )
                );

                $insertionColumns[] = $columns;

                $usedColumns[] = $columns;

                if (count(array_unique($usedColumns, SORT_REGULAR)) !== 1) {
                    throw new Exception('Columns in arrays do not match');
                }

                $insertionValues[] = $this->concludeBrackets(
                    implode(', ', $this->wrapStringInPita($columnWithValue))
                );
            }

            $insertionValues = implode(', ', $insertionValues);

            $this->bind('insert', [
                $insertionColumns[array_key_first($insertionColumns)],
                'VALUES',
                $insertionValues,
                $odku ? $odkuPostfix : '',
            ]);

            if ($ignore) {
                switch ($this->getDriver()) {
                    case AvailableDbmsDrivers::MARIADB:
                    case AvailableDbmsDrivers::MYSQL:
                        array_unshift($this->bindings['insert'], 'IGNORE');

                        break;
                    case AvailableDbmsDrivers::SQLITE:
                    case AvailableDbmsDrivers::POSTGRESQL:
                        $this->bind('insert', ['ON CONFLICT DO NOTHING']);

                        break;
                    case AvailableDbmsDrivers::ORACLE:
                    case AvailableDbmsDrivers::MSSQLSERVER:
                        $this->deleteBinding('insert');

                        $mergingTable = $this->wrapColumnInPita('moarai_source');

                        $columns = $this->wrapColumnInPita(
                            array_keys($columnsWithValues[array_key_first($columnsWithValues)])
                        );

                        $insertionColumns = $this->concludeBrackets(implode(', ', $columns));

                        $combinationExpression = [];

                        foreach ($columns as $column) {
                            $combinationExpression[] = $mergingTable
                                . '.'
                                . $column
                                . ' = '
                                . $table
                                . '.'
                                . $column;
                        }

                        $combinationExpression = implode(' AND ', $combinationExpression);

                        $insertionValues = [];

                        foreach ($columnsWithValues as $columnWithValues) {
                            $insertionValues[] = $this->concludeBrackets(
                                implode(
                                    ', ',
                                    $this->wrapStringInPita(array_values($columnWithValues))
                                )
                            );
                        }

                        $insertionValues = implode(', ', $insertionValues);

                        $this->bind('merge', [
                            'INTO',
                            $table,
                            'USING',
                            $mergingTable,
                            ' ON ',
                            $combinationExpression,
                            'WHEN NOT MATCHED THEN INSERT',
                            $insertionColumns,
                            'VALUES',
                            $insertionValues
                        ]);

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


    protected function updateClauseBinder(array $columnsWithValues)
    {
        $this->throwExceptionIfArrayIsNotAssociative($columnsWithValues);

        $whereExpression = $this->getBinding('where');

        $this->changeQueryTypeToUpdate();

        $expressionForUpdate = [];

        foreach ($columnsWithValues as $column => $value) {
            $expressionForUpdate[] = $this->wrapColumnInPita($column)
                . ' = '
                . $this->wrapStringInPita($value);
        }

        $this->bind('update', [
            'SET',
            implode(', ', $expressionForUpdate),
        ]);

        $this->bind('where', [$whereExpression]);

        return $this->executeQuery($this->pickUpThePieces($this->bindings));
    }


    public function joinClauseBinder(string|array $table,
                                     string $firstColumn,
                                     string $operator,
                                     string $secondColumn,
                                     string $joinType)
    {
        $this->throwExceptionIfOperatorIsInvalid($operator);

        $join = $joinType . 'Join';

        $this->renameBinding('join', $join);

        $table = $this->wrapColumnInPita($table);

        if (strpbrk($firstColumn, '.') && strpbrk($secondColumn, '.')) {
            $joinExpression = implode('.', $this->wrapColumnInPita(explode('.', $firstColumn)))
                . ' '
                . $operator
                . ' '
                . implode('.', $this->wrapColumnInPita(explode('.', $secondColumn)));
        } else {
            $joinExpression = $this->getTableBinding()
                . '.'
                . $this->wrapColumnInPita($firstColumn)
                . ' '
                . $operator
                . ' '
                . $table
                . '.'
                . $this->wrapColumnInPita($secondColumn);
        }

        $this->bind($join, [
            $table,
            'ON',
            $joinExpression
        ]);
    }

    public function unionClauseBinder($query, bool $all)
    {
        if (!$query instanceof QueryBuilder) {
            throw new Exception('The "query" argument must be an instance of the query builder.');
        }

        $bindings = $this->concludeBrackets($this->pickUpThePieces($this->getBindings()));

        $this->devastateBindings();

        $this->bindings[] = $bindings;

        $query = $this->concludeBrackets($this->pickUpThePieces($query->getBindings()));

        $this->bind('union', [
            $all ? 'ALL' : '',
            $query
        ]);
    }


    private function pickUpThePieces(array $bindings): string
    {
        $query = '';

        foreach ($bindings as $bindingName => $binding) {
            if (!empty($binding)) {
                if (is_string($bindingName)) {
                    $bindingName = implode(' ', preg_split('/(?=[A-Z])/', $bindingName));

                    $query .= strtoupper($bindingName);
                }

                if (is_array($binding)) {
                    $query .= ' ' . $this->pickUpThePieces($binding) . ' ';
                } else {
                    if (!strpbrk($binding, '()`\'"[]')) {
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
