<?php

namespace Moirai\DML;

use Exception;
use Moirai\Drivers\AvailableDbmsDrivers;
use Moirai\Drivers\MariaDbDriver;
use Moirai\Drivers\MsSqlServerDriver;
use Moirai\Drivers\MySqlDriver;
use Moirai\Drivers\OracleDriver;
use Moirai\Drivers\PostgreSqlDriver;
use Moirai\Drivers\SqliteDriver;

class QueryBuilder
{
    use ClauseBindersToolkit;

    /**
     * @var \Moirai\Drivers\MySqlDriver
     */
    protected $driver;

    /**
     * @var array|array[]
     */
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

    /**
     * QueryBuilder constructor.
     */
    public function __construct()
    {
        $this->driver = new PostgreSqlDriver();

        $this->useAdditionalAccessories();
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return $this->driver->getDriverName();
    }

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
     * @param string|int|float $value
     */
    protected function bindInWhereBeforeCheckingForThePresenceOfJson(string $conditionType,
                                                                     string $whereLogicalType,
                                                                     string $column,
                                                                     string $operator,
                                                                     string|int|float $value): void
    {
        if (stristr($column, '->')) {
            $fields = explode('->', $column);

            $column = $fields[0];

            unset($fields[0]);

            $expression = match ($this->getDriverName()) {
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
            $this->resolveLogicalType($conditionType, $whereLogicalType),
            $expression,
            $operator,
            $this->solveValueWrappingInPita($value)
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

    protected function devastateBindings(): void
    {
        $this->bindings = [];
    }

    /**
     * @param string $bindingName
     */
    protected function devastateBinding(string $bindingName): void
    {
        $this->bindings[$bindingName] = [];
    }

    /**
     * @param string $bindingName
     */
    protected function deleteBinding(string $bindingName): void
    {
        unset($this->bindings[$bindingName]);
    }




































    /**
     * @param bool $distinct
     * @param string|mixed ...$columns
     */
    protected function selectClauseBinder(bool $distinct = false, array|string ...$columns): void
    {
        $flattenedColumns = '*';

        if (!empty($columns[array_key_first($columns)])) {
            $flattenedColumns = implode(', ', $this->wrapColumnInPita($columns));
        }

        $selectBinding = $this->getBinding('select');

        if (!empty($selectBinding)) {
            $this->replaceBind('select', [
               [
                   $distinct || $selectBinding[0][0] === 'DISTINCT' ? 'DISTINCT' : '',
                   $selectBinding[0][1] . ', ' . $flattenedColumns
               ]
            ]);
        } else {
            $this->bind('select', [
                $distinct ? 'DISTINCT' : '',
                $flattenedColumns
            ]);
        }
    }

    /**
     * @param int|string $count
     * @param callable $callback
     * @return bool
     * @throws \Exception
     * TODO: change the return type of the sql query in loop
     */
    protected function chunkClauseBinder(int|string $count, callable $callback): bool
    {
        $this->throwExceptionIfArgumentNotNumeric($count);

        $this->limitClauseBinder($count, false);

        $page = 1;

        do {
            $stepData = $this->executeQuery($this->pickUpThePieces($this->getBindings()));

            $stepDataCount = count($stepData);

            if ($stepDataCount === 0) {
                break;
            }

            if ($callback($stepData, $page) === false) {
                return false;
            }

            $page++;
        } while ($stepDataCount === $count);

        return true;
    }

    /**
     * @param string $aggregateFunction
     * @param string|array $column
     * @param bool $distinct
     * @param bool $useColumnPita
     */
    protected function aggregateFunctionsClauseBinder(string $aggregateFunction,
                                                      string|array $column,
                                                      bool $distinct = false,
                                                      bool $useColumnPita = true): void
    {
        $aggregateFunction = strtoupper($aggregateFunction);

        $preparedColumn = '';

        if ($distinct) {
            $preparedColumn .= 'DISTINCT ';
        }

        if ($useColumnPita) {
            if (is_string($column)) {
                if ($column !== '*') {
                    $preparedColumn .= $this->wrapColumnInPita($column);
                }
            } elseif (is_array($column)) {
                $preparedColumn .= implode(', ', $this->wrapColumnInPita($column));
            }
        } else {
            if (is_string($column)) {
                $preparedColumn = $column;
            } elseif (is_array($column)) {
                $preparedColumn .= implode(', ', $column);
            }
        }

        $this->selectClauseBinder(false, $aggregateFunction . $this->concludeBrackets($preparedColumn));
    }

    /**
     * @param string $column
     * @param string $separator
     * @param bool $distinct
     */
    protected function groupConcatAggregateFunctionClauseBinder(string $column,
                                                                string $separator = ',',
                                                                bool $distinct = false): void
    {
        $driverName = $this->getDriverName();

        $aggregateFunction = match ($driverName) {
            AvailableDbmsDrivers::SQLITE,
            AvailableDbmsDrivers::MARIADB,
            AvailableDbmsDrivers::MYSQL => 'GROUP_CONCAT',
            AvailableDbmsDrivers::MS_SQL_SERVER,
            AvailableDbmsDrivers::POSTGRESQL => 'STRING_AGG',
            AvailableDbmsDrivers::ORACLE => 'LISTAGG'
        };

        if (in_array($driverName, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])) {
            $column = $this->wrapColumnInPita($column) . ' SEPARATOR ' . $this->wrapStringInPita($separator);
        } else {
            $column = $this->wrapColumnInPita($column) . ', ' . $this->wrapStringInPita($separator);
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column, $distinct, false);
    }

    /**
     * @param string $aggregateFunction
     * @param string|int $column
     * @throws \Exception
     */
    protected function bitAggregateFunctionClauseBinder(string $aggregateFunction, string|int $column): void
    {
        // TODO: for what ? (remove below line)
//        $this->throwExceptionIfArgumentNotNumeric($column);

        if (in_array($this->getDriverName(), [AvailableDbmsDrivers::SQLITE, AvailableDbmsDrivers::ORACLE])) {
            $this->throwExceptionIfDriverNotSupportFunction();
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);
    }

    /**
     * @param string $keyColumn
     * @param array $valueColumn
     * @throws \Exception
     */
    protected function jsonObjectAggregateFunctionClauseBinder(string $keyColumn, array $valueColumn)
    {
        $driverName = $this->getDriverName();

        $aggregateFunction = match ($driverName) {
            AvailableDbmsDrivers::POSTGRESQL => 'JSON_OBJECT_AGG',
            AvailableDbmsDrivers::SQLITE,
            AvailableDbmsDrivers::MS_SQL_SERVER => 'JSON_OBJECT',
            default => 'JSON_OBJECTAGG'
        };

        /**
         * If the Microsoft SQL Server driver is used, the keyColumn argument
         * is also treated as an element of the valueColumn argument.
         */
        if ($driverName === AvailableDbmsDrivers::MS_SQL_SERVER) {
            array_unshift($valueColumn, $keyColumn);

            foreach ($valueColumn as $key => $value) {
                $value = explode(':', $value);

                if (count($value) === 1) {
                    throw new Exception(
                        'When using MS SQL Server driver when you use function ' . __FUNCTION__
                        . ' you must specify the arguments in this form: \'key1:valueColumn1\', \'key2:valueColumn2\', ...'
                    );
                }

                $valueColumn[$key] = $this->wrapStringInPita($value[0]) . ':' . $value[1];
            }

            $this->aggregateFunctionsClauseBinder($aggregateFunction, $valueColumn, false, false);
        } else {
            if (empty($valueColumn)) {
                throw new Exception(
                    'When using all drivers except Microsoft SQL Server, when using function ' . __FUNCTION__
                    . ', the second argument is required.'
                );
            } elseif (count($valueColumn) > 1) {
                throw new Exception(
                    'When using all drivers except Microsoft SQL Server, when using function ' . __FUNCTION__
                    . ', the second argument must contain at most one element.'
                );
            }

            $this->aggregateFunctionsClauseBinder(
                $aggregateFunction, [$keyColumn, $valueColumn[array_key_first($valueColumn)]]
            );
        }
    }

    /**
     * @param string $column
     * @param bool $biased
     * @throws \Exception
     */
    protected function standardDeviationAggregateFunctionClauseBinder(string $column, bool $biased = false)
    {
        $driverName = $this->getDriverName();

        if ($driverName === AvailableDbmsDrivers::SQLITE) {
            throw new Exception(
                'Sqlite driver does not support this feature.'
            );
        }

        /**
         * ---------------------- MySQL, Maria Db ----------------------
         * | STD - population standard deviation.                      |
         * | STDDEV - population standard deviation.                   |
         * | STDDEV_POP - population standard deviation.               |
         * | STDDEV_SAMP - sample standard deviation.                  |
         * -------------------------------------------------------------
         * ------------------------ Postgre SQL ------------------------
         * | STDDEV - sample standard deviation of expression.         |
         * | STDDEV_POP - population standard deviation.               |
         * | STDDEV_SAMP -  sample standard deviation.                 |
         * -------------------------------------------------------------
         * ----------------------- MS SQL Server -----------------------
         * | STDEV - population standard deviation.                    |
         * | STDEV_POP - population standard deviation.                |
         * -------------------------------------------------------------
         * -------------------------- Oracle ---------------------------
         * | STDDEV -  sample standard deviation. It differs from      |
         * | STDDEV_SAMP in that STDDEV returns zero when              |
         * | it has only 1 row of input, whereas STDDEV_SAMP returns   |
         * | zero.                                                     |
         * | STDDEV_POP - population standard deviation.               |
         * | STDDEV_SAMP - sample standard deviation.                  |
         * -------------------------------------------------------------
         */

        if (!$biased) {
            // Population standard deviation
            $aggregateFunction = match ($driverName) {
                AvailableDbmsDrivers::MYSQL,
                AvailableDbmsDrivers::MARIADB => 'STDDEV',
                AvailableDbmsDrivers::POSTGRESQL ,
                AvailableDbmsDrivers::ORACLE => 'STDDEV_POP',
                AvailableDbmsDrivers::MS_SQL_SERVER => 'STDEV',
            };
        } else {
            // Sample standard deviation
            if ($driverName === AvailableDbmsDrivers::MS_SQL_SERVER) {
                throw new Exception(
                    'Microsoft SQL Server driver does not support this feature.'
                );
            }

            $aggregateFunction = 'STDDEV_SAMP';
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);
    }

    /**
     * @param string $column
     * @param bool $biased
     * @throws \Exception
     */
    protected function varianceAggregateFunctionClauseBinder(string $column, bool $biased = false)
    {
        $driverName = $this->getDriverName();

        if ($driverName === AvailableDbmsDrivers::SQLITE) {
            throw new Exception(
                'Sqlite driver does not support this feature.'
            );
        }

        /**
         * ---------------------- MySQL, Maria Db ----------------------
         * | VARIANCE - standard variance.                             |
         * | VAR_SAMP - sample variance.                               |
         * | VAR_POP - standard variance.                              |
         * -------------------------------------------------------------
         * ------------------------ Postgre SQL ------------------------
         * | VARIANCE - sample variance.                               |
         * | VAR_SAMP - sample variance.                               |
         * | VAR_POP - standard variance.                              |
         * -------------------------------------------------------------
         * ----------------------- MS SQL Server -----------------------
         * | VAR - sample variance.                                    |
         * | VARP - standard variance.                                 |
         * -------------------------------------------------------------
         * -------------------------- Oracle ---------------------------
         * | VARIANCE - sample variance.                               |
         * | VAR_SAMP - sample variance.                               |
         * | VAR_POP - standard variance.                              |
         * -------------------------------------------------------------
         */

        if (!$biased) {
            // Standard variance
            $aggregateFunction = $driverName !== AvailableDbmsDrivers::MS_SQL_SERVER ? 'VAR_POP' : 'VARP';
        } else {
            // Sample variance
            $aggregateFunction = $driverName !== AvailableDbmsDrivers::MS_SQL_SERVER ? 'VAR_SAMP' : 'VAR';
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);
    }


    protected function existsClauseBinder()
    {
        $query = $this->pickUpThePieces($this->bindings);

        $this->changeQueryType('select', false);

        $this->replaceBind('select', ['EXISTS' . $this->concludeBrackets($query)]);

        return $this->getClause();
    }

    /**
     * @param string $table
     */
    protected function fromClauseBinder(string $table): void
    {
        $this->bind('from', [$this->wrapColumnInPita($table)]);
    }

    /**
     * @param string $whereLogicalType
     * @param string $conditionType
     * @param string|array|callable $column
     * @param string|int|float|callable|null $operator
     * @param string|int|float|callable $value
     * @throws \Exception
     */
    protected function baseConditionClauseBinder(string $whereLogicalType,
                                                 string $conditionType,
                                                 string|array|callable $column,
                                                 string|int|float|callable|null $operator,
                                                 string|int|float|callable $value): void
    {
        if (is_array($column)) {
            if (!is_null($operator) || !empty($value)) {
                throw new Exception(
                    'Invalid argument in "' . $conditionType . '" function. If the first argument is passed as 
                    an array, then the following arguments must be omitted.'
                );
            }

            if ($this->isAssociative($column)) {
                $keys = array_keys($column);

                $columnFirstElementKey = $keys[0];

                if (count($column) === 1) {
                    $value = array_pop($column);

                    $column = $columnFirstElementKey;

                    $this->throwExceptionIfMisplacedArray($column);

                    $this->bindInWhereBeforeCheckingForThePresenceOfJson(
                        $conditionType,
                        $whereLogicalType,
                        $column,
                        '=',
                        $value
                    );
                } elseif (count($column) > 1) {
                    foreach ($column as $columnName => $columnValue) {
                        $this->throwExceptionIfMisplacedArray($columnValue);

                        if ($columnName === $columnFirstElementKey) {
                            $this->bindInWhereBeforeCheckingForThePresenceOfJson(
                                $conditionType,
                                $whereLogicalType,
                                $columnName,
                                '=',
                                $columnValue
                            );
                        } else {
                            $this->bindInWhereBeforeCheckingForThePresenceOfJson(
                                $conditionType,
                                'AND',
                                $columnName,
                                '=',
                                $columnValue
                            );
                        }
                    }
                }
            } else {
                if (count($column) === 3) {
                    foreach ($column as $columnValue) {
                        $this->throwExceptionIfMisplacedArray($columnValue);
                    }

                    $this->throwExceptionIfOperatorIsInvalid($column[1]);

                    $this->bindInWhereBeforeCheckingForThePresenceOfJson(
                        $conditionType,
                        $whereLogicalType,
                        $column[0],
                        $column[1],
                        $column[2]
                    );
                } else {
                    throw new Exception(
                        'Invalid argument in "' . $conditionType . '" function. When you set the first 
                        argument (argument "column") to be a non-associative array then it must have 3 elements 
                        (1. column, 2. operator, 3. value). Or instead you should use an associative array with 
                        key => value pairs where the key will be used as the column and the value as the value a in 
                        place of the operator the function will insert "=".'
                    );
                }
            }
        } elseif (is_string($column)) {
            if (!is_callable($operator) && !is_callable($value)) {
                if (empty($value) && !in_array($value, [0, '0'])) {
                    if ($this->checkMatching($operator, $this->operators)) {
                        throw new Exception(
                            'Invalid argument in "' . $conditionType . '" function. If you pass the first two 
                            arguments (argument "column" and argument "operator") and skip the third (argument "value") 
                            then you must pass the second argument (argument "operator") as a value and not as an 
                            operator, since in this case the function inserts the "=" operator. If you want to 
                            explicitly specify an operator, then you must pass the second argument (argument "operator")
                            as an operator and the third (argument "value") as a value, or pass a non-associative array
                            where the first element is a column, the second element is an operator and the third element
                            is a value.'
                        );
                    }

                    $value = $operator;
                    $operator = '=';
                } else {
                    $this->throwExceptionIfOperatorIsInvalid($operator);
                }

                $this->bindInWhereBeforeCheckingForThePresenceOfJson(
                    $conditionType,
                    $whereLogicalType,
                    $column,
                    $operator,
                    $value
                );
            } else {
                if (is_callable($operator)) {
                    if (!empty($value)) {
                        throw new Exception(
                            'Misplaced parameter in "' . $conditionType . '" function. When you set the second 
                        argument (argument "operator") as the called function, the third argument becomes redundant, 
                        since the function automatically puts "=" instead of the operator, and the second argument 
                        (argument "operator") is used as the value, so there is no need to specify a "value" argument 
                        if you set the second argument (argument "operator") to be the function being called.'
                        );
                    }

                    $value = $operator;
                    $operator = '=';
                } elseif (is_callable($value)) {
                    if (is_null($operator)) {
                        throw new Exception(
                            'Missing argument in "' . $conditionType . '" function. When you set the third argument 
                        (argument "value") as the function to be called, the second argument (argument "operator") must 
                        be specified explicitly. It cannot be null.'
                        );
                    }

                    $this->throwExceptionIfOperatorIsInvalid($operator);
                }

                $this->bind($conditionType, [$whereLogicalType]);

                $this->bind($conditionType, [
                    $this->wrapColumnInPita($column),
                    $operator
                ]);

                $this->runCallbackForVirginInstance($conditionType, $value);
            }
        } elseif (is_callable($column)) {
            if (empty($operator)) {
                throw new Exception(
                    'Invalid argument in "' . $conditionType . '" function. When you set the first argument of 
                    a function "' . $conditionType . '" (argument "column") to be the function to call, the second 
                    argument (argument "operator") is required. It either must be an operator (but then the third 
                    argument (argument "value") must also be present) or it must be a value, but then the function "'
                    . $conditionType . '" puts "=" instead of the operator, and in place of the value it will use the 
                    second argument (argument "operator").'
                );
            } elseif (empty($value)) {
                if ($this->checkMatching($operator, $this->operators)) {
                    throw new Exception(
                        'Invalid argument in "' . $conditionType . '" function. When you set the first argument 
                        of a function "' . $conditionType . '" (argument "column") to be the function to call and the 
                        second argument (argument "operator") to be the operator, then you must fill in the third 
                        argument (argument "value"). Or you should skip the third one argument (argument "value") and 
                        instead of the second argument (argument "operator") put a value, in this case the function "'
                        . $conditionType . '" will use the second argument (argument "operator") as the value and put 
                        "=" instead of the operator.'
                    );
                }

                $value = $operator;
                $operator = '=';
            } else {
                $this->throwExceptionIfOperatorIsInvalid($operator);
            }

            $this->bind($conditionType, [$whereLogicalType]);

            $this->runCallbackForVirginInstance($conditionType, $column);

            $this->bind($conditionType, [
                $operator,
                $this->solveValueWrappingInPita($value)
            ]);
        }
    }

    /**
     * @param string $whereLogicalType
     * @param string $column
     * @param array|string|int|float $range
     * @param string|int|float $endOfRange
     * @param bool $isNotCondition
     * @param bool $betweenColumns
     * @throws Exception
     */
    protected function whereBetweenClauseBinder(string $whereLogicalType,
                                                string $column,
                                                array|string|int|float $range,
                                                string|int|float $endOfRange,
                                                bool $isNotCondition = false,
                                                bool $betweenColumns = false): void
    {
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

            $startOfRange = $range[0];
            $endOfRange = $range[1];
        } else {
            if (empty($endOfRange)) {
                throw new Exception('Range end not specified');
            }

            $startOfRange = $range;
        }

        if ($betweenColumns) {
            $startOfRange = $this->wrapColumnInPita($startOfRange);
            $endOfRange = $this->wrapColumnInPita($endOfRange);
        } else {
            if (!is_numeric($startOfRange)) {
                $startOfRange = $this->wrapStringInPita($startOfRange);
            }

            if (!is_numeric($endOfRange)) {
                $endOfRange = $this->wrapStringInPita($endOfRange);
            }
        }

        $this->bind('where', [
            $this->resolveLogicalType('where', $whereLogicalType),
            $this->wrapColumnInPita($column),
            $isNotCondition ? 'NOT' : '',
            'BETWEEN',
            $startOfRange . ' AND ' . $endOfRange
        ]);
    }

    /**
     * @param string $whereLogicalType
     * @param string|array $firstColumn
     * @param string|null $operator
     * @param string|null $secondColumn
     * @param bool $isNotCondition
     * @throws Exception
     */
    protected function whereColumnClauseBinder(string $whereLogicalType,
                                               string|array $firstColumn,
                                               string|null $operator,
                                               string|null $secondColumn,
                                               bool $isNotCondition = false): void
    {
        if (is_array($firstColumn)) {
            if (!empty($operator) || !empty($secondColumn)) {
                throw new Exception(
                    'If the first argument is an array, then the following arguments must be omitted.'
                );
            }

            $columns = $firstColumn;

            $firstKey = array_key_first($columns);

            if (is_array($columns[$firstKey])) {
                foreach ($columns as $key => $column) {
                    if (!is_array($column)) {
                        throw new Exception(
                            'If you are using an array as first argument, and array first element also an array, 
                            then all other elements must also be arrays.'
                        );
                    }

                    if ($key !== $firstKey) {
                        $whereLogicalType = 'AND';
                    }

                    $this->whereColumnClauseBinder(
                        $whereLogicalType, $column, null, null, $isNotCondition
                    );
                }

                return;
            } else {
                if ($this->isAssociative($columns)) {
                    foreach ($columns as $key => $value) {
                        if ($key !== $firstKey) {
                            $whereLogicalType = 'AND';
                        }

                        $this->bind('where', [
                            $this->resolveLogicalType('where', $whereLogicalType),
                            $isNotCondition ? 'NOT' : '',
                            $this->wrapColumnInPita($key),
                            '=',
                            $this->wrapColumnInPita($value),
                        ]);
                    }

                    return;
                } else {
                    if (count($columns) === 2) {
                        $firstColumn = $columns[0];
                        $operator = '=';
                        $secondColumn = $columns[1];
                    } elseif (count($columns) === 3) {
                        $firstColumn = $columns[0];
                        $operator = $columns[1];
                        $secondColumn = $columns[2];

                        $this->throwExceptionIfOperatorIsInvalid($operator);
                    } else {
                        throw new Exception(
                            'If array not associative, array must contain 2 (column and value) or 3 (column, 
                        operator and value) elements. If you pass 2 elements, by default operator will be used "=".'
                        );
                    }
                }
            }
        } elseif (is_string($firstColumn)) {
            if (empty($operator)) {
                throw new Exception(
                    'If the first argument is passed as a string (not an array) then you must pass the second 
                    argument as an operator and the third argument as the second column, or the second argument as the 
                    second column but in this case the "=" will be used as operator.'
                );
            }

            if (!empty($secondColumn)) {
                $this->throwExceptionIfOperatorIsInvalid($operator);
            } else {
                $secondColumn = $operator;
                $operator = '=';
            }
        }

        $this->bind('where', [
            $this->resolveLogicalType('where', $whereLogicalType),
            $isNotCondition ? 'NOT' : '',
            $this->wrapColumnInPita($firstColumn),
            $operator,
            $this->wrapColumnInPita($secondColumn),
        ]);
    }

    /**
     * @param string $whereLogicalType
     * @param callable $callback
     * @param bool $isNotCondition
     */
    protected function whereExistsClauseBinder(string $whereLogicalType,
                                               callable $callback,
                                               bool $isNotCondition = false)
    {
        $this->bind('where', [
            $this->resolveLogicalType('where', $whereLogicalType),
            $isNotCondition ? 'NOT' : '',
            'EXISTS'
        ]);

        $this->runCallbackForVirginInstance(
            'where',
            $callback
        );
    }

    /**
     * @param string $whereLogicalType
     * @param string|array $column
     * @param string $value
     * @param string $searchModifier
     * @param string|array|null $rankingColumn
     * @param string|int|array $normalizationBitmask
     * @param bool|array $highlighting
     * @param bool $isNotCondition
     * @throws \Exception
     */
    protected function whereFullTextClauseBinder(string $whereLogicalType,
                                                 string|array $column,
                                                 string $value,
                                                 string $searchModifier,
                                                 string|array|null $rankingColumn,
                                                 string|int|array $normalizationBitmask,
                                                 bool|array $highlighting,
                                                 bool $isNotCondition = false): void
    {
        switch ($this->getDriverName()) {
            case AvailableDbmsDrivers::MARIADB:
            case AvailableDbmsDrivers::MYSQL:
                $this->throwExceptionIfFtsModifierIsInvalid($searchModifier);

                if (!is_array($column)) {
                    $column = [$column];
                }

                $this->bind('where', [
                    $this->resolveLogicalType('where', $whereLogicalType),
                    $isNotCondition ? 'NOT' : '',
                    'MATCH',
                    $this->concludeBrackets(implode(', ', $this->wrapColumnInPita($column))),
                    'AGAINST',
                    $this->concludeBrackets($this->wrapStringInPita($value) . ' ' . $searchModifier)
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

                    array_walk($weights, function ($value, $key) use (&$weights) {
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

                    $this->selectClauseBinder(false, $glowworms);
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

                    foreach ($rankingColumn as $item) {
                        $relevancyColumn = 'rank_' . $item;

                        $relevancyColumns[] = $relevancyColumn;

                        if ($weighing) {
                            $item = $this->concludeEntities(
                                $this->wrapColumnInPita($item),
                                'setweight(' . $valueOpenExpression,
                                '), ' . $this->wrapStringInPita($weights[$item]) . ')'
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

                    $this->selectClauseBinder(false, $columnsForRankingByRelevance);

                    $this->orderByClauseBinder($relevancyColumns, 'desc');
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
                    $this->resolveLogicalType('where', $whereLogicalType),
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
                    $this->resolveLogicalType('where', $whereLogicalType),
                    $column,
                    $isNotCondition ? 'NOT' : '',
                    'MATCH',
                    $value
                ]);

                break;
            case AvailableDbmsDrivers::MS_SQL_SERVER:
                if (!is_array($column)) {
                    $column = [$column];
                } else {
                    $this->throwExceptionIfArrayAssociative($column);
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

                $this->orderByClauseBinder(
                    $this->wrapColumnInPita('fts_table')
                    . '.'
                    . $this->wrapColumnInPita('rank'),
                    'desc'
                );

                break;
            case AvailableDbmsDrivers::ORACLE:
                if (!is_array($column)) {
                    $column = [$column];
                } else {
                    $this->throwExceptionIfArrayAssociative($column);
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

                $this->selectClauseBinder(false, $scores);

                $this->bind('where', [
                    $this->resolveLogicalType('where', $whereLogicalType),
                    implode(' OR ', $containers)
                ]);

                $this->orderByClauseBinder($scores, 'desc');

                break;
        }
    }

    /**
     * @param string $whereLogicalType
     * @param string $column
     * @param array $setOfSupposedVariables
     * @param bool $isNotCondition
     * @throws Exception
     */
    protected function whereInClauseBinder(string $whereLogicalType,
                                           string $column,
                                           array $setOfSupposedVariables,
                                           bool $isNotCondition = false): void
    {
        $this->throwExceptionIfArrayAssociative(
            $setOfSupposedVariables,
            'Array for variables cannot be associative'
        );

        if (empty($setOfSupposedVariables)) {
            throw new Exception('Array for values cannot be empty');
        }

        $this->bind('where', [
            $this->resolveLogicalType('where', $whereLogicalType),
            $this->wrapColumnInPita($column),
            $isNotCondition ? 'NOT' : '',
            'IN',
            $this->concludeBrackets(implode(', ', $setOfSupposedVariables))
        ]);
    }

    /**
     * @param string $whereLogicalType
     * @param string $column
     * @param bool $isNotCondition
     */
    protected function whereNullClauseBinder(string $whereLogicalType,
                                             string $column,
                                             bool $isNotCondition = false)
    {
        $this->bind('where', [
            $this->resolveLogicalType('where', $whereLogicalType),
            $this->wrapColumnInPita($column),
            'IS',
            $isNotCondition ? 'NOT' : '',
            'NULL'
        ]);
    }

    /**
     * @param string $whereLogicalType
     * @param string $column
     * @param string|array $value
     * @throws \Exception
     */
    protected function whereJsonContainsClauseBinder(string $whereLogicalType, string $column, string|array $value)
    {
        $driver = $this->getDriverName();

        if (in_array($driver, [AvailableDbmsDrivers::SQLITE, AvailableDbmsDrivers::ORACLE])) {
            $this->throwExceptionIfDriverNotSupportFunction();
        }

        if (in_array($driver, [
            AvailableDbmsDrivers::MYSQL,
            AvailableDbmsDrivers::MARIADB,
            AvailableDbmsDrivers::POSTGRESQL
        ])) {
            if (is_array($value)) {
                if (count($value) > 1) {
                    $value = '[' . implode(', ', $this->concludeDoubleQuotes($value)) . ']';
                } else {
                    $value = $value[0];
                }
            } else {
                $value = $this->concludeDoubleQuotes($value);
            }
        } else {
            if (is_array($value)) {
                $value = $value[0];
            }
        }

        $subsequenceWithColumn = $this->divideSubsequenceFromSequence($column);

        $column = $subsequenceWithColumn['column'];

        $subsequence = $subsequenceWithColumn['subsequence'];

        $expression = match ($driver) {
            AvailableDbmsDrivers::MARIADB,
            AvailableDbmsDrivers::MYSQL => 'JSON_CONTAINS' . $this->concludeBrackets(
                    $this->wrapColumnInPita($column)
                    . ', '
                    . $value
                    . $subsequence
                ),
            AvailableDbmsDrivers::POSTGRESQL => $this->concludeBrackets(
                    $this->wrapColumnInPita($column) . $subsequence
                ) . '::jsonb @> ' . $value,
            AvailableDbmsDrivers::MSSQLSERVER => $value . ' IN ' . $this->concludeBrackets(
                    'SELECT [VALUE] FROM OPENJSON'
                    . $this->concludeBrackets($this->wrapColumnInPita($column) . $subsequence)
                ),
        };

        $this->bind('where', [
            $this->resolveLogicalType('where', $whereLogicalType),
            $expression
        ]);
    }

    /**
     * @param string $whereLogicalType
     * @param string $column
     * @param string $operator
     * @param string|int|null $value
     * @throws \Exception
     */
    protected function whereJsonLengthClauseBinder(string $whereLogicalType,
                                                   string $column,
                                                   string $operator,
                                                   string|int|null $value)
    {
        $driver = $this->getDriverName();

        if (in_array($driver, [AvailableDbmsDrivers::SQLITE, AvailableDbmsDrivers::ORACLE])) {
            $this->throwExceptionIfDriverNotSupportFunction();
        }

        if (is_null($value)) {
            if (!$this->checkMatching($operator, $this->operators)) {
                $value = $operator;

                $operator = '=';
            } else {
                throw new Exception(
                    'Argument value is required if an operator is specified. If a value is specified instead 
                    of an operator argument, then the function uses the operator "=".'
                );
            }
        } else {
            $this->throwExceptionIfOperatorIsInvalid($operator);
        }

        $subsequenceWithColumn = $this->devideSubsequenceFromSequence($column);

        $column = $subsequenceWithColumn['column'];

        $subsequence = $subsequenceWithColumn['subsequence'];

        $subsequence = $this->wrapColumnInPita($column) . $subsequence;

        $expression = match ($driver) {
            AvailableDbmsDrivers::MARIADB,
            AvailableDbmsDrivers::MYSQL => 'JSON_LENGTH' . $this->concludeBrackets($subsequence),
            AvailableDbmsDrivers::POSTGRESQL => 'JSONB_ARRAY_LENGTH' . $this->concludeBrackets(
                    $this->concludeBrackets($subsequence) . '::jsonb'
                ),
            AvailableDbmsDrivers::MSSQLSERVER => $this->concludeBrackets(
                'SELECT COUNT(*) FROM OPENJSON' . $this->concludeBrackets($subsequence)
            )
        };

        $expression .= ' ' . $operator . ' ' . $value;

        $this->bind('where', [
            $this->resolveLogicalType('where', $whereLogicalType),
            $expression
        ]);
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

            $orderByBinding = $this->getBinding('orderBy');

            if (!empty($orderByBinding)) {
                $this->replaceBind('orderBy', [
                    [
                        $orderByBinding[0][0] . ', ' . $column . ' ' . ($needDirection ? $direction : ''),
                    ]
                ]);
            } else {
                $this->bind('orderBy', [
                    $column . ' ' . ($needDirection ? $direction : '')
                ]);
            }
        } else {
            $randomExpression = match ($this->getDriverName()) {
                AvailableDbmsDrivers::MARIADB,
                AvailableDbmsDrivers::MYSQL => 'RAND()',
                AvailableDbmsDrivers::POSTGRESQL,
                AvailableDbmsDrivers::SQLITE => 'RANDOM()'
            };

            $this->replaceBind('orderBy', [
                [
                    $randomExpression
                ]
            ]);
        }
    }

    protected function groupByClauseBinder(string|array ...$columns)
    {
        $this->bind('groupBy', [
            $this->wrapColumnInPita($columns)
        ]);
    }

    // TODO integrate pgsql
    protected function offsetClauseBinder(int $count)
    {
        if ($this->getDriverName() === AvailableDbmsDrivers::ORACLE) {
            $count .= ' ROWS';
        }

        $this->bind('offset', [$count]);
    }

    protected function limitClauseBinder(int $count, bool $inPercentages)
    {
        if ($this->getDriverName() !== AvailableDbmsDrivers::ORACLE) {
            if ($inPercentages) {
                throw new Exception(
                    '"'
                    . $this->getDriverName()
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


        return $this->executeQuery($this->pickUpThePieces($this->getBindings()));
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

                    $readyUpdate .= match ($this->getDriverName()) {
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

                switch ($this->getDriverName()) {
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
                switch ($this->getDriverName()) {
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

    protected function updateClauseBinder(array $columnsWithValues, bool $operationIsUsed = false)
    {
        $this->throwExceptionIfArrayIsNotAssociative($columnsWithValues);

        $whereExpression = $this->getBinding('where');

        $this->changeQueryTypeToUpdate();

        $expressionForUpdate = [];

        $driver = $this->getDriverName();

        foreach ($columnsWithValues as $column => $value) {
            $lockThisIteration = false;

            if (stristr($column, '->')) {
                if ($driver === AvailableDbmsDrivers::ORACLE) {
                    throw new Exception('DriverInterface "Oracle" does not support updating json column values.');
                }

                if ($driver === AvailableDbmsDrivers::MSSQLSERVER) {
                    $lockThisIteration = true;
                }

                $subsequence = $this->divideSubsequenceFromSequence($column, true);

                $column = $subsequence['column'];

                $expression = match ($driver) {
                    AvailableDbmsDrivers::SQLITE,
                    AvailableDbmsDrivers::MARIADB,
                    AvailableDbmsDrivers::MYSQL => 'JSON_SET' . $this->concludeBrackets(
                            $this->wrapColumnInPita($column)
                            . $subsequence['subsequence']
                            . ', '
                            . $this->wrapStringInPita($value)
                        ),
                    AvailableDbmsDrivers::POSTGRESQL => 'JSONB_SET' . $this->concludeBrackets(
                            $this->wrapColumnInPita($column)
                            . '::jsonb'
                            . $subsequence['subsequence']
                            . ', '
                            . $this->wrapStringInPita($value)
                        ),
                    AvailableDbmsDrivers::MSSQLSERVER => 'JSON_VALUE' . $this->concludeBrackets(
                            $this->wrapColumnInPita($column)
                            . $subsequence['subsequence']
                        ) . ' = ' . $this->wrapStringInPita($value)
                };
            } else {
                $expression = !$operationIsUsed ? $this->wrapStringInPita($value) : $value;
            }

            if (!$lockThisIteration) {
                $expression = $this->wrapColumnInPita($column) . ' = ' . $expression;
            }

            $expressionForUpdate[] = $expression;
        }

        $this->bind('update', [
            'SET',
            implode(', ', $expressionForUpdate),
        ]);

        if (!empty($whereExpression)) {
            $this->bind('where', [$whereExpression]);
        }

        return $this->executeQuery($this->pickUpThePieces($this->getBindings()));
    }

    protected function unaryOperatorsClauseBinder(string|array $columns,
                                                  int|float|string|null $amount = null,
                                                  string $operator = '+')
    {
        $preparedColumns = [];

        if (is_array($columns)) {
            $this->throwExceptionIfArrayIsNotAssociative($columns);

            foreach ($columns as $column => $amount) {
                $this->throwExceptionIfArgumentNotNumeric($amount);

                $preparedColumns[$column] = $this->wrapColumnInPita($column) . ' ' . $operator . ' ' . $amount;
            }
        } else {
            $this->throwExceptionIfArgumentNotNumeric($amount);

            $preparedColumns[$columns] = $this->wrapColumnInPita($columns) . ' ' . $operator . ' ' . $amount;
        }

        $this->updateClauseBinder($preparedColumns, true);
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

        $this->bind('union', [$all ? 'ALL' : '', $query]);

        $unionBindings = $this->pickUpThePieces($this->getBindings());

        $this->resetBindingsToDefault();

        $this->bind('union', [$unionBindings]);

        $this->renameBinding('union', 'evasive');
    }

    protected function deleteClauseBinder(string|null $uniqueValue, string $uniqueColumn)
    {
        $whereBinding = $this->getBinding('where');

        if (!empty($uniqueValue)) {
            $uniqueValue = $this->wrapStringInPita($uniqueValue);

            $uniqueColumn = $this->wrapColumnInPita($uniqueColumn);

            $table = $this->getTableBinding();

            if (!empty($whereBinding)) {
                $whereBinding[] = 'AND';
            }

            $whereBinding[] = $table . '.' . $uniqueColumn . ' = ' . $uniqueValue;
        }

        $this->changeQueryTypeToDelete();

        if (!empty($whereBinding)) {
            $this->bind('where', [$whereBinding]);
        }

        return $this->executeQuery($this->pickUpThePieces($this->getBindings()));
    }

    protected function truncateClauseBinder()
    {
        $driver = $this->getDriverName();

        if ($driver !== AvailableDbmsDrivers::SQLITE) {
            $this->changeQueryTypeToTruncate();

            if ($driver === AvailableDbmsDrivers::POSTGRESQL) {
                $this->bind('truncate', ['RESTART IDENTITY CASCADE']);
            }

            $response = $this->executeQuery($this->pickUpThePieces($this->getBindings()));
        } else {
            $table = $this->getTableBinding();

            $this->changeQueryTypeToDelete();

            $response = $this->executeQuery($this->pickUpThePieces($this->getBindings()));

            $this->devastateBindings();

            $this->bind('from', ['SQLITE_SEQUENCE']);

            $this->bind('where', [
                $this->wrapColumnInPita('name') . ' = ' . $this->wrapStringInPita(trim($table, '"'))
            ]);

            $this->updateClauseBinder(['SEQ' => 0]);
        }

        return $response;
    }

    protected function lockClauseBinder($isSharedLock = true): void
    {
        $driver = $this->getDriverName();

        if ($driver === AvailableDbmsDrivers::SQLITE) {
            $this->throwExceptionIfDriverNotSupportFunction();
        }

        if ($driver === AvailableDbmsDrivers::MSSQLSERVER) {
            $lockingExpression = 'WITH(rowlock, %s holdlock)';

            $plug = '';

            if (!$isSharedLock) {
                $plug = 'updlock,';
            }

            $this->bind('from', [sprintf($lockingExpression, $plug)]);
        } else {
            if ($isSharedLock) {
                $lockingExpression = match ($driver) {
                    AvailableDbmsDrivers::MARIADB,
                    AvailableDbmsDrivers::MYSQL => 'LOCK IN SHARE MODE',
                    AvailableDbmsDrivers::POSTGRESQL => 'FOR SHARE',
                    AvailableDbmsDrivers::ORACLE => 'IN ROW SHARE MODE'
                };
            } else {
                $lockingExpression = 'FOR UPDATE';
            }

            $this->bindings[] = [$lockingExpression];
        }
    }

    private function pickUpThePieces(array $bindings): string
    {
        $query = '';
dd($bindings);

        foreach ($bindings as $bindingName => $binding) {
            if (empty($binding) && $binding !== 0) {
                continue;
            }

            if (is_string($bindingName)) {
                if ($bindingName !== 'evasive') {
                    $bindingName = implode(' ', preg_split('/(?=[A-Z])/', $bindingName));

                    $query .= strtoupper($bindingName);
                }
            }

            if (is_array($binding)) {
                $query .= ' ' . $this->pickUpThePieces($binding) . ' ';
            } else {
                if ($binding instanceof self) {
                    $binding = $binding->pickUpThePieces($binding->getBindings());
                } else {
                    if (!strpbrk($binding, '()`\'"[]')) {
                        $binding = strtoupper($binding);
                    }
                }

                $query .= ' ' . $binding . ' ';
            }
        }

        return trim(
            preg_replace('/\s+/', ' ', $query)
        );
    }

    private function executeQuery(string $statement)
    {
//        dd($statement);

        return $statement;

//        return 0;
    }
}
