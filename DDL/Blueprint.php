<?php

namespace Moirai\DDL;

use Closure;
use Exception;
use Moirai\DDL\Shared\Actions;
use Moirai\DDL\Shared\AlterActions;
use Moirai\DDL\Shared\DataTypes;
use Moirai\DDL\Shared\Indexes;
use Moirai\Drivers\DriverInterface;
use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\Constraints\DefinedColumnConstraints;
use Moirai\Drivers\AvailableDbmsDrivers;
use Moirai\Drivers\PostgreSqlDriver;
use Moirai\Drivers\OracleDriver;
use Moirai\DML\QueryBuilderRepresentativeSpokesman;

class Blueprint
{
    /**
     * @var \Moirai\Drivers\DriverInterface
     */
    private DriverInterface $driver;

    /**
     * @var string
     */
    private string $table;

    /**
     * @var string
     */
    private string $action;

    /**
     * @var array
     */
    public array $columnsDefinitionsBindings = [];

    /**
     * @var array
     */
    private array $tableConstraintsBindings = [];

    /**
     * @var array
     */
    private array $alterActionsBindings = [];

    /**
     * @var array
     */
    private array $chainedStatements = [];

    /**
     * @var array
     */
    public array $modify = [];

    /**
     * Blueprint constructor.
     *
     * @param \Moirai\Drivers\DriverInterface $driver
     * @param string $table
     * @param string $action
     * @param \Closure|null $callback
     */
    public function __construct(DriverInterface $driver, string $table, string $action, Closure|null $callback = null)
    {
        $this->driver = $driver;
        $this->table = $table;
        $this->action = $action;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getColumnsDefinitions(): array
    {
        return $this->sewColumnsBindings();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTableConstraintsDefinitions(): array
    {
        return $this->sewTableConstraintsBindings();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAlterActionsDefinitions(): array
    {
        $addColumnsActions = [];

        if (in_array($this->driver::class, [AvailableDbmsDrivers::MS_SQL_SERVER, AvailableDbmsDrivers::ORACLE])) {
            $addColumnsActions[] = str_replace(
                '{definition}',
                implode(', ', $this->getColumnsDefinitions()),
                $this->driver->getLexis()->getAlterAction(AlterActions::ADD_COLUMN)
            );
        } else {
            foreach ($this->getColumnsDefinitions() as $columnDefinition) {
                $addColumnsActions[] = str_replace(
                    '{definition}',
                    $columnDefinition,
                    $this->driver->getLexis()->getAlterAction(AlterActions::ADD_COLUMN)
                );
            }
        }

        return [
            'add_columns_actions' => $addColumnsActions,
            'other_actions' => $this->sewAlterActionsBindings()
        ];
    }

    /**
     * @return array
     */
    public function getChainedStatements(): array
    {
        return $this->chainedStatements;
    }

    /**
     * @param string $column
     * @param int $dataType
     * @param array|null $parameters
     * @param array|null $constraints
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    private function bindColumnDefinition(
        string $column,
        int $dataType,
        array|null $parameters = null,
        array|null $constraints = null
    ): DefinedColumnConstraints {
        $this->columnsDefinitionsBindings[$column] = [
            'data_type' => $dataType,
            'parameters' => $parameters,
            'constraints' => $constraints
        ];

        return new DefinedColumnConstraints($column, $this);
    }

    /**
     * @param int $type
     * @param array $parameters
     * @throws \Exception
     */
    private function bindTableConstraint(int $type, array $parameters): void
    {
        if ($type === TableConstraints::PRIMARY_KEY) {
            if (!empty(array_filter($this->tableConstraintsBindings, function ($tableConstraint) {
                return $tableConstraint['type'] === TableConstraints::PRIMARY_KEY;
            }))) {
                throw new Exception('Primary key already exists in table "' . $this->table . '".');
            }
        }

        $this->tableConstraintsBindings[] = compact('type', 'parameters');
    }

    /**
     * @param int $action
     * @param array $parameters
     */
    private function bindAlterAction(int $action, array $parameters = []): void
    {
        $this->alterActionsBindings = compact('action', 'parameters');
    }

    /**
     * @param string $type
     * @param array $parameters
     * @throws \Exception
     */
    private function bindIndex(string $type, array $parameters): void
    {
        $this->chainedStatements[] = str_replace(
            array_map(fn($key) => '{' . $key . '}', array_keys($parameters)),
            array_values($parameters),
            $this->driver->getLexis()->getIndex($type)
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function sewColumnsBindings(): array
    {
        $sewedColumns = [];

        foreach ($this->columnsDefinitionsBindings as $column => $options) {
            $definitionSignature = $this->driver->getLexis()->getDataType($options['data_type']);

            foreach ($options['parameters'] as $parameterKey => $parameterValue) {
                $parameterKey = '{' . $parameterKey . '}';

                if (!is_null($parameterValue)) {
                    if (!str_contains($definitionSignature, $parameterKey)) {
                        throw new Exception(
                            'DBMS driver "'
                            . $this->driver->getName()
                            . '" do not support parameters for data type "'
                            . $definitionSignature . '".'
                        );
                    }
                } else {
                    $parameterKey = '({' . $parameterKey . '})';
                    $parameterValue = '';
                }

                $definitionSignature = str_replace(
                    $parameterKey,
                    $parameterValue,
                    $definitionSignature
                );
            }

            foreach ($options['constraints'] as $constraintKey => $constraintParameters) {
                $constraintDefinitionSignature = $this->driver->getLexis()->getColumnConstraint($constraintKey);

                if ($constraintKey === ColumnConstraints::COMMENT
                    && in_array($this->driver::class, [PostgreSqlDriver::class, OracleDriver::class])) {
                    $constraintParameters['table'] = $this->table;
                    $constraintParameters['column'] = $column;
                }

                foreach ($constraintParameters as $constraintParameterKey => $constraintParameterValue) {
                    $constraintDefinitionSignature = str_replace(
                        '{' . $constraintParameterKey . '}',
                        $constraintParameterValue,
                        $constraintDefinitionSignature
                    );
                }

                if ($constraintKey === ColumnConstraints::COMMENT
                    && in_array($this->driver::class, [PostgreSqlDriver::class, OracleDriver::class])) {
                    $this->chainedStatements[] = $constraintDefinitionSignature;
                } else {
                    $definitionSignature .= ' ' . $constraintDefinitionSignature;
                }
            }

            $sewedColumns[$column] = $column . ' ' . $definitionSignature;
        }

        return $sewedColumns;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function sewTableConstraintsBindings(): array
    {
        $sewedTableConstraints = [];

        $tableConstraintParameterKeyToPlaceholder = [
            'name' => 'CONSTRAINT {name}',
            'on_delete_action' => 'ON DELETE {on_delete_action}',
            'on_update_action' => 'ON UPDATE {on_update_action}'
        ];

        foreach ($this->tableConstraintsBindings as $tableConstraint) {
            $definitionSignature = $this->driver->getLexis()->getTableConstraint($tableConstraint['type']);

            foreach ($tableConstraint['parameters'] as $parameterKey => $parameterValue) {
                if (is_null($parameterValue)
                    && isset($tableConstraintParameterKeyToPlaceholder[$parameterKey])) {
                    $definitionSignature = str_replace(
                        $tableConstraintParameterKeyToPlaceholder[$parameterKey],
                        '',
                        $definitionSignature
                    );
                }

                if (in_array($parameterKey, ['on_delete_action', 'on_update_action'])
                    && !in_array($parameterValue, $this->driver->getAllowedForeignKeyActions())) {
                    throw new Exception(
                        'DBMS driver "'
                        . $this->driver->getName()
                        . '" does not support "'
                        . $parameterValue
                        . '" action as foreign key action.'
                    );
                }

                $definitionSignature = str_replace(
                    '{' . $parameterKey . '}',
                    $parameterValue,
                    $definitionSignature
                );
            }

            $sewedTableConstraints[] = $definitionSignature;
        }

        return $sewedTableConstraints;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function sewAlterActionsBindings(): array
    {
        $sewedAlterActions = [];

        foreach ($this->alterActionsBindings as $alterAction) {
            $sewedAlterActions[] = strtr(
                $this->driver->getLexis()->getTableConstraint($alterAction['action']),
                array_map(
                    fn($key) => '{' . $key . '}',
                    array_keys($alterAction['parameters'])
                )
            );
        }

        return $sewedAlterActions;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define boolean data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function boolean(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BOOLEAN);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define boolean data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Same as "boolean".                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function bool(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BOOLEAN);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define bit data type column.                                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "size" - represents the number of bits.                       |
     * |     Unavailable - MS SQL Server                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string $size
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function bit(string $column, int|string $size = 1): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::BIT,
            in_array($this->driver::class, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])
                ? compact('size')
                : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define tiny integer data type column.                        |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::TINY_INTEGER,
            null,
            array_filter([
                ColumnConstraints::AUTOINCREMENT => $autoIncrement,
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned tiny integer data type column.               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * | Same as "tinyInteger" with the "unsigned" argument specified as        |
     * | "true".                                                                |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedTinyInteger(string $column, bool $autoIncrement = false): DefinedColumnConstraints
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define small integer data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function smallInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::SMALL_INTEGER,
            null,
            array_filter([
                ColumnConstraints::AUTOINCREMENT => $autoIncrement,
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned small integer data type column.              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * | Same as "smallInteger" with the "unsigned" argument specified as       |
     * | "true".                                                                |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedSmallInteger(string $column, bool $autoIncrement = false): DefinedColumnConstraints
    {
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define medium integer data type column.                      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function mediumInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::MEDIUM_INTEGER,
            null,
            array_filter([
                ColumnConstraints::AUTOINCREMENT => $autoIncrement,
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned medium integer data type column.             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * | Same as "mediumInteger" with the "unsigned" argument specified as      |
     * | "true".                                                                |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedMediumInteger(string $column, bool $autoIncrement = false): DefinedColumnConstraints
    {
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define integer data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::INTEGER,
            null,
            array_filter([
                ColumnConstraints::AUTOINCREMENT => $autoIncrement,
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned integer data type column.                    |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | Same as "integer" with the "unsigned" argument specified as "true".    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedInteger(string $column, bool $autoIncrement = false): DefinedColumnConstraints
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define big integer data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::BIG_INTEGER,
            null,
            array_filter([
                ColumnConstraints::AUTOINCREMENT => $autoIncrement,
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned big integer data type column.                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * | Same as "bigInteger" with the "unsigned" argument specified as "true". |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): DefinedColumnConstraints
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define float data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - PostgreSQl                                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function float(string $column, int|string|null $precision = null, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::FLOAT,
            compact('precision'),
            array_filter([
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned float data type column.                      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - PostgreSQl                                           |
     * |                                                                        |
     * | Same as "float" with the "unsigned" argument specified as "true".      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedFloat(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->float($column, $precision, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define binary float data type column.                        |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function binaryFloat(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BINARY_FLOAT,);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define double data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - PostgreSQl                                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function double(string $column, int|string|null $precision = null, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::DOUBLE,
            compact('precision'),
            array_filter([
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned float data type column.                      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - PostgreSQl                                           |
     * |                                                                        |
     * | Same as "double" with the "unsigned" argument specified as "true".     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedDouble(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->double($column, $precision, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define binary double data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function binaryDouble(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BINARY_DOUBLE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define decimal data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariDB, PostgreSQL, MS SQL Server, SQLite                       |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function decimal(
        string $column,
        int|string|null $precision = null,
        int|string|null $scale = null,
        bool $unsigned = false
    ): DefinedColumnConstraints {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::DECIMAL,
            [
                'precision_and_scale' => !is_null($scale) ? $precision . ', ' . $scale : $precision
            ],
            array_filter([
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned decimal data type column.                    |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * |                                                                        |
     * | Same as "decimal" with the "unsigned" argument specified as "true".    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedDecimal(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnConstraints
    {
        return $this->decimal($column, $precision, $scale, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define numeric data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariDB, PostgreSQL, MS SQL Server, SQLite                       |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @param bool $unsigned
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function numeric(
        string $column,
        int|string|null $precision = null,
        int|string|null $scale = null,
        bool $unsigned = false
    ): DefinedColumnConstraints {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::NUMERIC,
            [
                'precision_and_scale' => !is_null($scale) ? $precision . ', ' . $scale : $precision
            ],
            array_filter([
                ColumnConstraints::UNSIGNED => $unsigned
            ])
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned numeric data type column.                    |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariDB, PostgreSQL, MS SQL Server, SQLite                       |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * |     Unavailable - SQLite                                               |
     * |                                                                        |
     * | Same as "numeric" with the "unsigned" argument specified as "true".    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function unsignedNumeric(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnConstraints
    {
        return $this->numeric($column, $precision, $scale, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define number data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents the total number of digits that can  |
     * | be stored.                                                             |
     * |     Required - No                                                      |
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function number(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::NUMBER,
            [
                'precision_and_scale' => !is_null($scale) ? $precision . ', ' . $scale : $precision
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define real data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function real(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::REAL);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define small money data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function smallMoney(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::SMALL_MONEY);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define money data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function money(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::MONEY);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define char data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle       |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function char(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::CHAR, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define nchar data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server, Oracle                                                  |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function nchar(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::N_CHAR, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define varchar data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, SQLite                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - MySQL, MariaDB, MS SQL Server                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function varchar(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::VARCHAR, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define varchar2 data type column.                            |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - no                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function varchar2(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::VARCHAR_2, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define nvarchar data type column.                            |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - no                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function nvarchar(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::N_VARCHAR, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define nvarchar2 data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - no                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function nvarchar2(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::N_VARCHAR_2, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define tiny text data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function tinyText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TINY_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define medium text data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function mediumText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::MEDIUM_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define text data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, SQLite                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function text(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define long text data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function longText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::LONG_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define n text data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function nText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::N_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define tiny blob data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function tinyBlob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TINY_BLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define medium blob data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function mediumBlob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::MEDIUM_BLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define blob data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Oracle, SQLite                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function blob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define long blob data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function longBlob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::LONG_BLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define set data type column.                                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param array $whiteList
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function set(string $column, array $whiteList): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::SET,
            [
                'white_list' => implode(', ', $whiteList)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define enum data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param array $whiteList
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function enum(string $column, array $whiteList): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::ENUM,
            [
                'white_list' => implode(', ', $whiteList)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define json data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server                              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function json(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::JSON);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define jsonb data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function jsonb(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::JSONB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define binary data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - MS SQL Server                                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function binary(string $column, string|int|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BINARY, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define varbinary data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - MS SQL Server                                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function varbinary(string $column, string|int|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::VARBINARY, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define uuid data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function uuid(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::UUID);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unique identifier data type column.                   |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * | Same as "uuid".                                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function uniqueIdentifier(string $column): DefinedColumnConstraints
    {
        return $this->uuid($column);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define xml data type column.                                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server, Oracle                                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function xml(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::XML);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define xmlType data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server, Oracle                                      |
     * | ---------------------------------------------------------------------- |
     * | Same as "xml".                                                         |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function xmlType(string $column): DefinedColumnConstraints
    {
        return $this->xml($column);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define image data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function image(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::IMAGE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define sql_variant data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function sqlVariant(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::SQL_VARIANT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define rowversion data type column.                          |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function rowVersion(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::ROW_VERSION);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define clob (Character Large Object) data type column.       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function clob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::CLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define clob (Character Large Object) data type column.       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Same as "clob".                                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function characterLargeObject(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::CLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define nclob (National Character Large Object) data type     |
     * | column.                                                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function nclob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::NCLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define nclob (National Character Large Object) data type     |
     * | column.                                                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Same as "nclob"                                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function notationalCharacterLargeObject(string $column): DefinedColumnConstraints
    {
        return $this->nclob($column);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define raw data type column.                                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int $length
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function raw(string $column, string|int $length): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::RAW, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define long data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function long(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::LONG);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define urowid data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function urowid(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::UROWID);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define bytea data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function bytea(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BYTEA);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define hstore data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function hstore(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::HSTORE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define inet data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function inet(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::INET);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define cidr (Classless Inter-Domain Routing notation) data   |
     * | type column.                                                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function cidr(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::CIDR);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define cidr (Classless Inter-Domain Routing notation) data   |
     * | type column.                                                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Same as "cidr".                                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function classlessInterDomainRoutingNotation(string $column): DefinedColumnConstraints
    {
        return $this->cidr($column);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define date data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function date(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::DATE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define datetime data type column.                            |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server, SQLite                                  |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function dateTime(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::DATE_TIME);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define datetime2 data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function dateTime2(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::DATE_TIME_2, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define smalldatetime data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function smallDateTime(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::SMALL_DATE_TIME);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define datetimeoffset data type column.                      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function dateTimeOffset(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::DATE_TIME_OFFSET, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define time data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server                              |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function time(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TIME, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define timestamp data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, Oracle                                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * |     Unavailable - MS SQL Server                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function timestamp(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TIMESTAMP, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define year data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function year(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::YEAR);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define time with time zone data type column.                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function timeTz(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TIME_TZ, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define time with time zone data type column.                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * |                                                                        |
     * | Same as "timeTz".                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function timeWithTimezone(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->timeTz($column, $precision);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define timestamp with time zone data type column.            |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, Oracle                                                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * |     Unavailable - PostgreSQL                                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function timestampTz(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TIMESTAMP_TZ, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define timestamp with time zone data type column.            |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, Oracle                                                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * |     Unavailable - PostgreSQL                                           |
     * |                                                                        |
     * | Same as "timestampTz".                                                 |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function timestampWithTimezone(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->timestampTz($column, $precision);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define timestamp with local time zone data type column.      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function timestampLtz(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::TIMESTAMP_LTZ, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define timestamp with local time zone data type column.      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * |                                                                        |
     * | Same as "timestampLtz".                                                |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function timestampWithLocalTimeZone(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->timestampLtz($column, $precision);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define interval year to month data type column.              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function intervalYearToMonth(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::INTERVAL_YEAR_TO_MONTH);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define interval day to second data type column.              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "dayPrecision" - represents  the precision of the day         |
     * | (determines how many digits are stored).                               |
     * |                                                                        |
     * | Argument "secondPrecision" - represents  the precision of the second   |
     * | (determines how many digits are stored).                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $dayPrecision
     * @param int|string|null $secondPrecision
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function intervalDayToSecond(
        string $column,
        int|string|null $dayPrecision = null,
        int|string|null $secondPrecision = null
    ): DefinedColumnConstraints {
        return $this->bindColumnDefinition(
            $column,
            DataTypes::INTERVAL_DAY_TO_SECOND,
            [
                'day_precision' => $dayPrecision,
                'second_precision' => $secondPrecision
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define geometry data type column.                            |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function geometry(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::GEOMETRY);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define geometry collection data type column.                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function geometryCollection(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::GEOMETRY_COLLECTION);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define point data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function point(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::POINT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define multipoint data type column.                          |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function multipoint(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::MULTI_POINT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define line data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function line(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::LINE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define linestring data type column.                          |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function linestring(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::LINE_STRING);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define multilinestring data type column.                     |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function multilinestring(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::MULTI_LINE_STRING);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define polygon data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function polygon(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::POLYGON);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define multipolygon data type column.                        |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function multipolygon(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::MULTI_POLYGON);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define geography data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function geography(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::GEOGRAPHY);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define hierarchyid data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function hierarchyId(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::HIERARYCHYID);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define lseg (line segment) data type column.                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function lSeg(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::LSEG);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define lseg (line segment) data type column.                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Same as "lSeg".                                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function lineSegment(string $column): DefinedColumnConstraints
    {
        return $this->lSeg($column);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define box data type column.                                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function box(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::BOX);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define circle data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    public function circle(string $column): DefinedColumnConstraints
    {
        return $this->bindColumnDefinition($column, DataTypes::CIRCLE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define check table constraint.                               |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | Creating - MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite   |
     * | Altering - MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle           |
     * | ---------------------------------------------------------------------- |
     * | Argument "expression" - driver expression to check a some condition.   |
     * |     Examples - column >= value                                         |
     * |                column between value1 and value2                        |
     * |                column1 < value and column2 != value                    |
     * |                ...                                                     |
     * --------------------------------------------------------------------------
     * @param string $expression
     * @param string|null $name
     * @throws \Exception
     */
    public function check(string $expression, string|null $name = null): void
    {
        if ($this->action === Actions::ALTER) {
            $this->bindAlterAction(
                AlterActions::ADD_CHECK_CONSTRAINT,
                compact('name', 'expression')
            );

            return;
        }

        $this->bindTableConstraint(TableConstraints::CHECK, compact('name', 'expression'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop check constraint.                                       |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropCheck(string $name): void
    {
        $this->bindAlterAction(AlterActions::ADD_CHECK_CONSTRAINT, compact('name'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unique table constraint.                              |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "columns" - column(s) that will be unique.                    |
     * | If an array is passed, these values will be specified in the           |
     * | expression line of this constraint. If you need to specify several     |
     * | columns, but not in row of this constraint, you must specify those     |
     * | columns in a separate constraint.                                      |
     * --------------------------------------------------------------------------
     * @param string|array $columns
     * @param string|null $name
     * @throws \Exception
     */
    public function unique(string|array $columns, string|null $name = null): void
    {
        $parameters = [
            'name' => $name,
            'columns' => implode(', ', $columns)
        ];

        if ($this->action === Actions::ALTER) {
            $this->bindAlterAction(AlterActions::ADD_UNIQUE_CONSTRAINT, $parameters);

            return;
        }

        $this->bindTableConstraint(TableConstraints::UNIQUE, $parameters);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define primary key table constraint.                         |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | Creating - MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite   |
     * | Altering - MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle           |
     * | ---------------------------------------------------------------------- |
     * | Argument "columns" - column(s) that will be primary key(s).            |
     * --------------------------------------------------------------------------
     * @param string|array $columns
     * @param string|null $name
     * @throws \Exception
     */
    public function primaryKey(string|array $columns, string|null $name = null): void
    {
        $parameters = [
            'name' => $name,
            'columns' => implode(', ', $columns)
        ];

        if ($this->action === Actions::ALTER) {
            $this->bindAlterAction(AlterActions::ADD_PRIMARY_KEY_CONSTRAINT, $parameters);

            return;
        }

        $this->bindTableConstraint(TableConstraints::PRIMARY_KEY, $parameters);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop primary key table constraint.                           |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of constraint.                              |
     * |     Required - PostgreSQL, MS SQL Server, Oracle                       |
     * --------------------------------------------------------------------------
     * @param string|null $name
     * @throws \Exception
     */
    public function dropPrimaryKey(string|null $name = null): void
    {
        $this->bindAlterAction(
            AlterActions::ADD_PRIMARY_KEY_CONSTRAINT,
            !in_array($this->driver::class, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])
                ? compact('name')
                : []
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define foreign key table constraint.                         |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | Creating - MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite   |
     * | Altering - MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle           |
     * | ---------------------------------------------------------------------- |
     * | Argument "columns" - the column(s) in the current table that holds the |
     * | value that will reference another table's primary or unique key. It is |
     * | the foreign key column(s).                                             |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "referencedTable" - this is the referenced table. It is the   |
     * | table that contains the column(s) you're linking to.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "referencedColumns" - the column(s) in the referenced table   |
     * | that the foreign key will match against.                               |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "onDelete" - the action that should be taken when a record in |
     * | the referenced (parent) table is deleted.                              |
     * |     Required - no                                                      |
     * |     Available values - Moirai\DDL\Shared\ForeignKeyActions             |
     * |                                                                        |
     * | Argument "onUpdate" - the action that should be taken when a record in |
     * | the referenced (parent) table is updated.                              |
     * |     Required - no                                                      |
     * |     Unavailable - Oracle                                               |
     * |     Available values - Moirai\DDL\Shared\ForeignKeyActions             |
     * |                                                                        |
     * | Argument "name" - the name of constraint.                              |
     * |     Required - no                                                      |
     * --------------------------------------------------------------------------
     * @param string|array $columns
     * @param string $referencedTable
     * @param string|array $referencedColumns
     * @param string|null $onDelete
     * @param string|null $onUpdate
     * @param string|null $name
     * @throws \Exception
     */
    public function foreignKey(
        string|array $columns,
        string $referencedTable,
        string|array $referencedColumns,
        string|null $onDelete = null,
        string|null $onUpdate = null,
        string|null $name = null
    ): void {
        $parameters = [
            'name' => $name,
            'columns' => implode(', ', $columns),
            'referenced_table' => $referencedTable,
            'referenced_columns' => implode(', ', $referencedColumns),
            'on_delete_action' => $onDelete,
            'on_update_action' => $onUpdate
        ];

        if ($this->action === Actions::ALTER) {
            $this->bindAlterAction(AlterActions::ADD_FOREIGN_KEY_CONSTRAINT, $parameters);

            return;
        }

        $this->bindTableConstraint(TableConstraints::FOREIGN_KEY, $parameters);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop foreign key table constraint.                           |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropForeignKey(string $name): void
    {
        $this->bindAlterAction(AlterActions::DROP_FOREIGN_KEY_CONSTRAINT, compact('name'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define index.                                                |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function index(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::INDEX,
            [
                'name' => $name,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define index.                                                |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function uniqueIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::UNIQUE,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define index.                                                |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string|array $columns
     * @throws \Exception
     */
    public function primaryKeyIndex(string|array $columns): void
    {
        $this->bindIndex(
            Indexes::PRIMARY_KEY,
            [
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define full text index.                                      |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function fullTextIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::FULL_TEXT,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define spatial index.                                        |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MySQL, MariaDB, MS SQL Server, Oracle                                  |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function spatialIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::SPATIAL,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define hash index.                                           |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MySQL, MariaDB, PostgreSQL                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function hashIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::HASH,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define invisible index.                                      |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function invisibleIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::INVISIBLE,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define gin (Generalized Inverted Index).                     |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function gin(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::GIN,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define gin (Generalized Inverted Index).                     |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Same as "gin".                                                         |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function generalizedInvertedIndex(string $name, string|array $columns): void
    {
        $this->gin($name, $columns);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define gist (Generalized Search Tree) index.                 |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function gistIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::GIST,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define gist (Generalized Search Tree) index.                 |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Same as "gistIndex".                                                   |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function generalizedSearchTreeIndex(string $name, string|array $columns): void
    {
        $this->gistIndex($name, $columns);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define spgist (Space-partitioned Generalized Search Tree)    |
     * | index.                                                                 |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function spgistIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::SPGIST,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define spgist (Space-partitioned Generalized Search Tree)    |
     * | index.                                                                 |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Same as "spgistIndex".                                                 |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function spacePartitionedGeneralizedSearchTreeIndex(string $name, string|array $columns): void
    {
        $this->spgistIndex($name, $columns);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define brin (Block Range Index).                             |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function brin(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::BRIN,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define brin (Block Range Index).                             |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Same as "brin".                                                        |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function blockRangeIndex(string $name, string|array $columns): void
    {
        $this->brin($name, $columns);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define bloom index.                                          |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function bloomIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::BLOOM,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define partial index.                                        |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL, MS SQL Server, Oracle, SQLite                              |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "expression" - expression or column. For Oracle expression    |
     * | will be used as column. You must specify column.                       |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @param string|array $expression
     * @throws \Exception
     */
    public function partialIndex(string $name, string|array $columns, string|array $expression): void
    {
        $this->bindIndex(
            Indexes::PARTIAL,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns),
                'expression' => $expression
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define filtered index.                                       |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | PostgreSQL, MS SQL Server, Oracle, SQLite                              |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "expression" - expression or column. For Oracle expression    |
     * | will be used as column. You must specify column.                       |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Same as "partialIndex".                                                |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @param string|array $expression
     * @throws \Exception
     */
    public function filteredIndex(string $name, string|array $columns, string|array $expression): void
    {
        $this->partialIndex($name, $columns, $expression);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define clustered index.                                      |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MS SQL Server, Oracle                                                  |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function clusteredIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::CLUSTERED,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define cluster index.                                        |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MS SQL Server, Oracle                                                  |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Same as "clusteredIndex".                                              |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function clusterIndex(string $name, string|array $columns): void
    {
        $this->clusteredIndex($name, $columns);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define non clustered index.                                  |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function nonClusteredIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::NON_CLUSTERED,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define xml index.                                            |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function xmlIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::XML,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define columnstore index.                                    |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function columnStoreIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::COLUMNSTORE,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define include index.                                        |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @param string|array $includedColumns
     * @throws \Exception
     */
    public function includeIndex(string $name, string|array $columns, string|array $includedColumns): void
    {
        $this->bindIndex(
            Indexes::INCLUDE,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns),
                'included_columns' => implode(', ', $includedColumns),
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define bitmap index.                                         |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function bitmapIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::BITMAP,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define reverse index.                                        |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function reverseIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::REVERSE,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define global index.                                         |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function globalIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::GLOBAL,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define local index.                                          |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function localIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::LOCAL,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define compress index.                                       |
     * | ---------------- DBMS drivers that support this index ---------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of index.                                   |
     * |     Required - yes                                                     |
     * |                                                                        |
     * | Argument "columns" - column(s) that will be indexed.                   |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $name
     * @param string|array $columns
     * @throws \Exception
     */
    public function compressIndex(string $name, string|array $columns): void
    {
        $this->bindIndex(
            Indexes::COMPRESS,
            [
                'name' => $name,
                'table' => $this->table,
                'columns' => implode(', ', $columns)
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop index.                                                  |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the index.                               |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropIndex(string $name): void
    {
        if (!in_array($this->driver::class, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])) {
            $this->chainedStatements[] = str_replace(
                '{name}',
                $name,
                $this->driver->getLexis()->getAlterAction(AlterActions::DROP_INDEX)
            );

            return;
        }

        $this->bindAlterAction(AlterActions::DROP_INDEX, compact('name'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to rename column.                                               |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "newName" - the name of the column that will be renamed.      |
     * |     Required - yes                                                     |
     * | Argument "oldName" - the name to which this column should be renamed.  |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $oldName
     * @param string $newName
     * @throws \Exception
     */
    public function renameColumn(string $oldName, string $newName): void
    {
        $parameters = [
            'old_name' => $oldName,
            'new_named' => $newName
        ];

        if (in_array($this->driver::class, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])) {
            $queryBuilder = new QueryBuilderRepresentativeSpokesman($this->driver::class);

            $column = $queryBuilder
                ->from('INFORMATION_SCHEMA.COLUMNS')
                ->select([
                    'COLUMN_NAME',
                    'DATA_TYPE',
                    'CHARACTER_MAXIMUM_LENGTH',
                    'NUMERIC_PRECISION',
                    'NUMERIC_SCALE',
                    'IS_NULLABLE',
                    'COLUMN_DEFAULT',
                    'EXTRA'
                ])
                ->where('TABLE_NAME', $this->table)
                ->where('TABLE_SCHEMA', 'db_name')
                ->where('COLUMN_NAME ', $oldName)
                ->get();

            $definition = $column['DATA_TYPE'];

            if (in_array($column['DATA_TYPE'], ['float', 'double', 'decimal', 'numeric', 'bit', 'timestamp', 'time'])) {
                $columnParameters = '';

                if ($column['NUMERIC_PRECISION']) {
                    $columnParameters = $column['NUMERIC_PRECISION'];

                    if (in_array($column['DATA_TYPE'], ['float', 'double', 'decimal', 'numeric']) && $column['NUMERIC_SCALE']) {
                        $columnParameters .= ', ' . $column['NUMERIC_SCALE'];
                    }
                }

                if ($columnParameters) {
                    $definition .= '(' . $columnParameters . ')';
                }
            } elseif (in_array($column['DATA_TYPE'], ['char', 'varchar', 'binary', 'varbinary'])
                && $column['CHARACTER_MAXIMUM_LENGTH']) {
                $definition .= '(' . $column['CHARACTER_MAXIMUM_LENGTH'] . ')';
            } elseif (in_array($column['DATA_TYPE'], ['enum', 'set']) && $column['COLUMN_TYPE']) {
                if ($column['COLUMN_TYPE']) {
                    $definition .= '('
                        . str_replace(
                            "'",
                            '',
                            substr(
                                $column['COLUMN_TYPE'],
                                strpos($column['COLUMN_TYPE'], '(') + 1,
                                -1
                            )
                        )
                        . ')';
                }
            }

            if ($column['IS_NULLABLE'] == 'NO') {
                $definition .= ' NOT NULL';
            }

            if ($column['COLUMN_DEFAULT'] !== null) {
                $definition .= ' DEFAULT ' . $column['COLUMN_DEFAULT'];
            }

            if ($column['EXTRA']) {
                if (str_contains($column['EXTRA'], 'auto_increment')) {
                    $definition .= ' AUTO_INCREMENT';
                }

                if (str_contains($column['EXTRA'], 'unsigned')) {
                    $definition .= ' UNSIGNED';
                }

                if (str_contains($column['EXTRA'], 'on update')) {
                    $definition .= ' ON UPDATE CURRENT_TIMESTAMP';
                }
            }

            $parameters['definition'] = $definition;
        }

        $this->bindAlterAction(AlterActions::RENAME_COLUMN, $parameters);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop column.                                                 |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the column.                              |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropColumn(string $name): void
    {
        $this->bindAlterAction(AlterActions::DROP_COLUMN, compact('name'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to set default value for column.                                |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL, MS SQL Server, Oracle                                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "column" - the name of the column.                            |
     * |     Required - yes                                                     |
     * | Argument "value" - the default value of the column.                    |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string $value
     * @throws \Exception
     */
    public function setDefault(string $column, int|string $value): void
    {
        $this->bindAlterAction(AlterActions::SET_DEFAULT, compact('column', 'value'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop default value of column.                                |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the column. For MS SQL Server the name   |
     * | of the constraint.                                                     |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropDefault(string $name): void
    {
        $this->bindAlterAction(AlterActions::DROP_DEFAULT, compact('name'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to enable keys of table.                                        |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @throws \Exception
     */
    public function enableKeys(): void
    {
        $this->bindAlterAction(AlterActions::ENABLE_KEYS);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to disable keys of table.                                       |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @throws \Exception
     */
    public function disableKeys(): void
    {
        $this->bindAlterAction(AlterActions::DISABLE_KEYS);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to add computed column.                                         |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server                              |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the column.                              |
     * |     Required - yes                                                     |
     * | Argument "expression" - the expression.                                |
     * |     Required - yes                                                     |
     * | Argument "definition" - the definition of column.                      |
     * |     Not Required - MySQL, MariaDB, PostgreSQL                          |
     * --------------------------------------------------------------------------
     * @param string $name
     * @param string $expression
     * @param string|null $definition
     * @throws \Exception
     */
    public function addComputedColumn(string $name, string $expression, string|null $definition = null): void
    {
        $this->bindAlterAction(
            AlterActions::ADD_COMPUTED_COLUMN,
            compact('name', 'expression', 'definition')
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop computed column.                                        |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the column.                              |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropComputedColumn(string $name): void
    {
        $this->bindAlterAction(AlterActions::DROP_COMPUTED_COLUMN, compact('name'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to lock table.                                                  |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @throws \Exception
     */
    public function lockTable(): void
    {
        $this->chainedStatements[] = str_replace(
            '{table}',
            $this->table,
            $this->driver->getLexis()->getAlterAction(AlterActions::LOCK_TABLE)
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to unlock table.                                                |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @throws \Exception
     */
    public function unlockTable(): void
    {
        $definition = $this->driver->getLexis()->getAlterAction(AlterActions::UNLOCK_TABLE);

        $this->chainedStatements[] = !in_array(
            $this->driver::class, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB]
        )
            ? str_replace('{table}', $this->table, $definition)
            : $definition;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to rename table.                                                |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "newName" - the new name of the table.                        |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     *
     * @param string $newName
     * @throws \Exception
     */
    public function renameTable(string $newName): void
    {
        if ($this->driver::class === AvailableDbmsDrivers::MS_SQL_SERVER) {
            $this->chainedStatements[] = str_replace(
                [
                    '{table}',
                    '{new_name}'
                ],
                [
                    $this->table,
                    $newName
                ],
                $this->driver->getLexis()->getAlterAction(AlterActions::RENAME_TABLE)
            );

            return;
        }

        $this->bindAlterAction(
            AlterActions::RENAME_TABLE,
            [
                'new_name' => $newName,
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to change table engine.                                         |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * | Argument "engine" - the engine of the table.                           |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $engine
     * @throws \Exception
     */
    public function changeEngine(string $engine): void
    {
        $this->bindAlterAction(AlterActions::CHANGE_ENGINE, compact('engine'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to change row format.                                           |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * | Argument "format" - the format of the table.                           |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $format
     * @throws \Exception
     */
    public function changeRowFormat(string $format): void
    {
        $this->bindAlterAction(AlterActions::CHANGE_ROW_FORMAT, compact('format'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to change autoincrement.                                        |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * | Argument "format" - the format of the table.                           |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $value
     * @throws \Exception
     */
    public function changeAutoincrement(string $value): void
    {
        $this->bindAlterAction(AlterActions::CHANGE_AUTO_INCREMENT, compact('value'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to change tablespace.                                           |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | MySQL, MariaDB, PostgreSQL, Oracle                                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "format" - the format of the table.                           |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $value
     * @throws \Exception
     */
    public function changeTablespace(string $value): void
    {
        $this->bindAlterAction(AlterActions::CHANGE_TABLESPACE, compact('value'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to change storage type.                                         |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL, Oracle                                                     |
     * | ---------------------------------------------------------------------- |
     * | Argument "column" - the name of the column.                            |
     * |     Required - yes                                                     |
     * | Argument "storageType" - the storage type of the column.               |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $storageType
     * @throws \Exception
     */
    public function setStorage(string $column, string $storageType): void
    {
        $this->bindAlterAction(
            AlterActions::SET_STORAGE,
            [
                'column' => $column,
                'storage_type' => $storageType
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to add extension.                                               |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the extension.                           |
     * |     Required - yes                                                     |
     * | Argument "ifNotExists" - condition for checking extension existence.   |
     * |     Required - no                                                      |
     * --------------------------------------------------------------------------
     * @param string $name
     * @param bool $ifNotExists
     * @throws \Exception
     */
    public function addExtension(string $name, bool $ifNotExists = false): void
    {
        $statement = str_replace(
            '{name}',
            $name,
            $this->driver->getLexis()->getAlterAction(AlterActions::ADD_EXTENSION)
        );

        if ($ifNotExists) {
            $statement = str_replace(' IF NOT EXISTS', '', $statement);
        }

        $this->chainedStatements[] = $statement;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop extension.                                              |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the extension.                           |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropExtension(string $name): void
    {
        $this->chainedStatements[] = str_replace(
            '{name}',
            $name,
            $this->driver->getLexis()->getAlterAction(AlterActions::DROP_EXTENSION)
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to create sequence.                                             |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL, MS SQL Server, Oracle                                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the sequence.                            |
     * |     Required - yes                                                     |
     * | Argument "expression" - the expression of sequence.                    |
     * |     Required - MS SQL Server                                           |
     * --------------------------------------------------------------------------
     * @param string $name
     * @param string|null $expression
     * @throws \Exception
     */
    public function createSequence(string $name, string|null $expression = null): void
    {
        $this->chainedStatements[] = str_replace(
            [
                '{name}',
                '{expression}'
            ],
            [
                $name,
                $expression
            ],
            $this->driver->getLexis()->getAlterAction(AlterActions::CREATE_SEQUENCE)
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to drop sequence.                                               |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL, MS SQL Server, Oracle                                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the sequence.                            |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $name
     * @throws \Exception
     */
    public function dropSequence(string $name): void
    {
        $this->chainedStatements[] = str_replace(
            '{name}',
            $name,
            $this->driver->getLexis()->getAlterAction(AlterActions::DROP_SEQUENCE)
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to rename sequence.                                             |
     * | --------------- DBMS drivers that support this action ---------------- |
     * | PostgreSQL, MS SQL Server, Oracle                                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "name" - the name of the sequence.                            |
     * |     Required - yes                                                     |
     * | Argument "oldName" - the new name of the sequence.                     |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $name
     * @param string $newName
     * @throws \Exception
     */
    public function renameSequence(string $name, string $newName): void
    {
        $this->chainedStatements[] = str_replace(
            [
                'old_name',
                'new_name'
            ],
            [
                $name,
                $newName
            ],
            $this->driver->getLexis()->getAlterAction(AlterActions::RENAME_SEQUENCE)
        );
    }
}
