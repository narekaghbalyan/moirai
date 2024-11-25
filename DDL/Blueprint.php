<?php

namespace Moirai\DDL;

use Closure;
use Exception;
use Moirai\DDL\Constraints\DefinedColumnConstraints;
use Moirai\DDL\Constraints\DefinedForeignKeyActions;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\Drivers\AvailableDbmsDrivers;
use Moirai\Drivers\MySqlDriver;

class Blueprint
{
    /**
     * @var \Moirai\Drivers\PostgreSqlDriver
     */
    private $driver;

    /**
     * @var string
     */
    private string $table;

    /**
     * @var array
     */
    private array $columns = [];

    /**
     * @var array
     */
    private array $tableConstraints = [];

    /**
     * Blueprint constructor.
     *
     * @param string $table
     * @param \Closure|null $callback
     */
    public function __construct(string $table, Closure|null $callback = null)
    {
        $this->driver = new MySqlDriver();
        $this->table = $table;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return $this->driver->getDriverName();
    }

    /**
     * @param string $column
     * @param int $dataType
     * @param array|null $parameters
     * @param array|null $constraints
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    private function bindColumn(
        string $column,
        int $dataType,
        array|null $parameters = null,
        array|null $constraints = null
    ): DefinedColumnConstraints
    {
        $this->columns[$column] = [
            'data_type' => $dataType,
            'parameters' => $parameters,
            'constraints' => $constraints
        ];

        return new DefinedColumnConstraints($column, $this);
    }

    /**
     * @param string $type
     * @param string $name
     * @param string|array $values
     */
    private function bindTableConstraint(string $type, string $name, string|array $values)
    {
        if (in_array($type, [TableConstraints::PRIMARY_KEY])) {
            // this constraints can be specified once in table
            // ... check if already exists -> if yes -> exception, if no -> bind
        }

        $this->tableConstraints[] = [
            'type' => $type,
            'name' => $name,
            'value' => $values
        ];
    }






    /**
     * @return string
     * @throws \Exception
     */
    private function sewDefinedColumns(): string
    {
        if (empty($this->columns)) {
            return '';
        }

        $sewedColumns = [];

        foreach ($this->columns as $column => $options) {
            $definitionSignature = $this->driver->getDataType($options['data_type']);

            foreach ($options['parameters'] as $parameterKey => $parameterValue) {
                $definitionSignature = str_replace(
                    '{' . $parameterKey . '}',
                    !is_null($parameterValue) ? '(' . $parameterValue . ')' : '',
                    $definitionSignature
                );
            }

            if (!empty($options['accessories'])) {
                foreach ($options['accessories'] as $accessoryKey => $accessoryParameters) {
                    $accessory = $this->driver->getDdlAccessory($accessoryKey);

                    //'CHECK({column} >= 0)

                    if (!empty($accessoryParameters)) {
                        foreach ($accessoryParameters as $accessoryParameterKey => $accessoryParameterValue) {
                            $accessory = str_replace(
                                '{' . $accessoryParameterKey . '}',
                                $accessoryParameterValue,
                                $accessory
                            );
                        }
                    }

                    $definitionSignature .= ' ' . $accessory;
                }
            }

            $sewedColumns[] = $column . ' ' . $definitionSignature;
        }

        dd(implode(', ', $sewedColumns));
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
        return $this->bindColumn($column, DataTypes::BOOLEAN);
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
        return $this->bindColumn($column, DataTypes::BOOLEAN);
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
        return $this->bindColumn(
            $column,
            DataTypes::BIT,
            in_array($this->getDriverName(), [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])
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
        return $this->bindColumn(
            $column,
            DataTypes::TINY_INTEGER,
            null,
            [
                'autoincrement' => $autoIncrement,
                'unsigned' => $unsigned
            ]
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
        return $this->bindColumn(
            $column,
            DataTypes::SMALL_INTEGER,
            null,
            [
                'autoincrement' => $autoIncrement,
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function mediumInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::MEDIUM_INTEGER,
            null,
            [
                'autoincrement' => $autoIncrement,
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::INTEGER,
            null,
            [
                'autoincrement' => $autoIncrement,
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::BIG_INTEGER,
            null,
            [
                'autoincrement' => $autoIncrement,
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function float(string $column, bool $unsigned = false, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::FLOAT,
            compact('precision'),
            [
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedFloat(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->float($column, true, $precision);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define binary float data type column.                        |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function binaryFloat(string $column, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::BINARY_FLOAT, null, ['unsigned' => $unsigned]);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned binary float data type column.               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | Same as "binaryFloat" with the "unsigned" argument specified as        |
     * | "true".                                                                |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedBinaryFloat(string $column): DefinedColumnConstraints
    {
        return $this->binaryFloat($column, true);
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
     * @param bool $unsigned
     * @param int|string|null $precision
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function double(string $column, bool $unsigned = false, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::DOUBLE,
            compact('precision'),
            [
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedDouble(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->double($column, true, $precision);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define binary double data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function binaryDouble(string $column,  bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::BINARY_DOUBLE, null, ['unsigned' => $unsigned]);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned binary double data type column.              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | Same as "binaryDouble" with the "unsigned" argument specified as       |
     * | "true".                                                                |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedBinaryDouble(string $column): DefinedColumnConstraints
    {
        return $this->binaryDouble($column, true);
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
     * @param bool $unsigned
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function decimal(
        string $column,
        bool $unsigned = false,
        int|string|null $precision = null,
        int|string|null $scale = null
    ): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::DECIMAL,
            [
                'precision_and_scale' => !is_null($scale) ? $precision . ', ' . $scale : $precision
            ],
            [
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedDecimal(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnConstraints
    {
        return $this->decimal($column, true, $precision, $scale);
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
     * @param bool $unsigned
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function numeric(
        string $column,
        bool $unsigned = false,
        int|string|null $precision = null,
        int|string|null $scale = null
    ): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::NUMERIC,
            [
                'precision_and_scale' => !is_null($scale) ? $precision . ', ' . $scale : $precision
            ],
            [
                'unsigned' => $unsigned
            ]
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedNumeric(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnConstraints
    {
        return $this->numeric($column, true, $precision, $scale);
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
     * @param bool $unsigned
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function number(
        string $column,
        bool $unsigned = false,
        int|string|null $precision = null,
        int|string|null $scale = null
    ): DefinedColumnConstraints
    {
        return $this->bindColumn(
            $column,
            DataTypes::NUMBER,
            [
                'precision_and_scale' => !is_null($scale) ? $precision . ', ' . $scale : $precision
            ],
            [
                'unsigned' => $unsigned
            ]
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned number data type column.                     |
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
     * |                                                                        |
     * | Same as "number" with the "unsigned" argument specified as "true".     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedNumber(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnConstraints
    {
        return $this->number($column, true, $precision, $scale);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define real data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function real(string $column, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::REAL, null, ['unsigned' => $unsigned]);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned real data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Same as "real" with the "unsigned" argument specified as "true".       |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedReal(string $column): DefinedColumnConstraints
    {
        return $this->real($column, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define small money data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function smallMoney(string $column, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::SMALL_MONEY, null, ['unsigned' => $unsigned]);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned small money data type column.                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Same as "smallMoney" with the "unsigned" argument specified as "true". |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedSmallMoney(string $column): DefinedColumnConstraints
    {
        return $this->smallMoney($column, true);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define money data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function money(string $column, bool $unsigned = false): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::MONEY, null, ['unsigned' => $unsigned]);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned money data type column.                      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * | Same as "money" with the "unsigned" argument specified as "true".      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function unsignedMoney(string $column): DefinedColumnConstraints
    {
        return $this->money($column, true);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function char(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::CHAR, compact('length'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function nchar(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::N_CHAR, compact('length'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function varchar(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::VARCHAR, compact('length'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function varchar2(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::VARCHAR_2, compact('length'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function nvarchar(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::N_VARCHAR, compact('length'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function nvarchar2(string $column, int|string|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::N_VARCHAR_2, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define tiny text data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function tinyText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TINY_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define medium text data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function mediumText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::MEDIUM_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define text data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, SQLite                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function text(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define long text data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function longText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::LONG_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define n text data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function nText(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::N_TEXT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define tiny blob data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function tinyBlob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TINY_BLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define medium blob data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function mediumBlob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::MEDIUM_BLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define blob data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Oracle, SQLite                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function blob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::BLOB);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define long blob data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function longBlob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::LONG_BLOB);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function set(string $column, array $whiteList): DefinedColumnConstraints
    {
        return $this->bindColumn(
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function enum(string $column, array $whiteList): DefinedColumnConstraints
    {
        return $this->bindColumn(
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function json(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::JSON);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define jsonb data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function jsonb(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::JSONB);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function binary(string $column, string|int|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::BINARY, compact('length'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function varbinary(string $column, string|int|null $length = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::VARBINARY, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define uuid data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function uuid(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::UUID);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function xml(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::XML);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function image(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::IMAGE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define sql_variant data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function sqlVariant(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::SQL_VARIANT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define rowversion data type column.                          |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function rowVersion(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::ROW_VERSION);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define clob (Character Large Object) data type column.       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function clob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::CLOB);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function nclob(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::NCLOB);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function raw(string $column, string|int $length): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::RAW, compact('length'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define long data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function long(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::LONG);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define urowid data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function urowid(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::UROWID);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define bytea data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function bytea(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::BYTEA);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define hstore data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function hstore(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::HSTORE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define inet data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function inet(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::INET);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function cidr(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::CIDR);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define date data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function date(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::DATE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define datetime data type column.                            |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server, SQLite                                  |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function dateTime(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::DATE_TIME);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function dateTime2(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::DATE_TIME_2, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define smalldatetime data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function smallDateTime(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::SMALL_DATE_TIME);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function dateTimeOffset(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::DATE_TIME_OFFSET, compact('precision'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function time(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TIME, compact('precision'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function timestamp(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TIMESTAMP, compact('precision'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define year data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function year(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::YEAR);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function timeTz(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TIME_TZ, compact('precision'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function timestampTz(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TIMESTAMP_TZ, compact('precision'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function timestampLtz(string $column, int|string|null $precision = null): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::TIMESTAMP_LTZ, compact('precision'));
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
     * @return \Moirai\DDL\DefinedColumnConstraints
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function intervalYearToMonth(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::INTERVAL_YEAR_TO_MONTH);
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function intervalDayToSecond(
        string $column,
        int|string|null $dayPrecision = null,
        int|string|null $secondPrecision = null
    ): DefinedColumnConstraints
    {
        return $this->bindColumn(
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
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function geometry(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::GEOMETRY);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define geometry collection data type column.                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function geometryCollection(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::GEOMETRY_COLLECTION);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define point data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     */
    public function point(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::POINT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define multipoint data type column.                          |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function multipoint(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::MULTI_POINT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define line data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function line(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::LINE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define linestring data type column.                          |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function linestring(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::LINE_STRING);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define multilinestring data type column.                     |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function multilinestring(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::MULTI_LINE_STRING);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define polygon data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function polygon(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::POLYGON);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define multipolygon data type column.                        |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function multipolygon(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::MULTI_POLYGON);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define geography data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function geography(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::GEOGRAPHY);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define hierarchyid data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function hierarchyId(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::HIERARYCHYID);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define lseg (line segment) data type column.                 |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnConstraints
     * @throws \Exception
     */
    public function lSeg(string $column): DefinedColumnConstraints
    {
        return $this->bindColumn($column, DataTypes::LSEG);
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
     * @throws \Exception
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
        return $this->bindColumn($column, DataTypes::BOX);
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
        return $this->bindColumn($column, DataTypes::CIRCLE);
    }

    /**
     * Table constraints
     */

    /**
     * --------------------------------------------------------------------------
     * | Clause to define check table constraint.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "expression" - driver expression to check a some condition.   |
     * |     Examples - column >= value                                         |
     * |                column between value1 and value2                        |
     * |                column1 < value and column2 != value                    |
     * |                ...                                                     |
     * --------------------------------------------------------------------------
     * @param string $expression
     * @param string|null $constraintName
     */
    public function check(string $expression, string|null $constraintName = null)
    {
        $this->bindTableConstraint(TableConstraints::CHECK, $constraintName, $expression);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unique table constraint.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "columns" - column(s) that will be unique.                    |
     * | If an array is passed, these values will be specified in the           |
     * | expression line of this constraint. If you need to specify several     |
     * | columns, but not in row of this constraint, you must specify those     |
     * | columns in a separate constraint.                                      |
     * --------------------------------------------------------------------------
     * @param string|array $columns
     * @param string|null $constraintName
     */
    public function unique(string|array $columns, string|null $constraintName = null)
    {
        $this->bindTableConstraint(TableConstraints::UNIQUE, $constraintName, implode(', ', $columns));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define primary key table constraint.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "columns" - column(s) that will be primary key(s).            |
     * --------------------------------------------------------------------------
     * @param string|array $columns
     * @param string|null $constraintName
     */
    public function primaryKey(string|array $columns, string|null $constraintName = null)
    {
        $this->bindTableConstraint(TableConstraints::PRIMARY_KEY, $constraintName, implode(', ', $columns));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define foreign key table constraint.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "columns" - the column(s) in the current table that holds the |
     * | value that will reference another table's primary or unique key. It is |
     * | the foreign key column(s).                                             |
     * |                                                                        |
     * | Argument "referencedTable" - this is the referenced table. It is the   |
     * | table that contains the column(s) you're linking to.                   |
     * |                                                                        |
     * | Argument "referencedColumns" - the column(s) in the referenced table   |
     * | that the foreign key will match against.                               |
     * --------------------------------------------------------------------------
     * @param string|array $columns
     * @param string $referencedTable
     * @param string|array $referencedColumns
     * @param string|null $constraintName
     *
     *
     * TODO actions on update and on delete
     */
    public function foreignKey(
        string|array $columns,
        string $referencedTable,
        string|array $referencedColumns,
        string|null $constraintName = null
    )
    {
        $this->bindTableConstraint(
            TableConstraints::FOREIGN_KEY,
            $constraintName,
            '('
            . implode(', ', $columns)
            . ') REFERENCES '
            . $referencedTable
            . ' ('
            . implode(', ', $referencedColumns)
            . ')'
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define index table constraint.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * | Argument "columns" - column(s) that will be primary key(s).            |
     * --------------------------------------------------------------------------
     *
     * TODO
     *
     * @param string|array $columns
     * @param string|null $constraintName
     */
    public function index(string|array $columns, string|null $constraintName = null)
    {
        $this->bindTableConstraint(TableConstraints::INDEX, $constraintName, implode(', ', $columns));
    }
}
