<?php

namespace Moirai\DDL;

use Closure;
use Exception;
use Moirai\Drivers\AvailableDbmsDrivers;
use Moirai\Drivers\MySqlDriver;

class Blueprint
{
    /**
     * @var \Moirai\Drivers\PostgreSqlDriver
     */
    protected $driver;

    /**
     * @var string
     */
    public string $table;

    /**
     * @var array
     */
    public array $columns = [];

    /**
     * @var array|array[]
     */
    public array $tableAccessories = [
        'unique' => [
            'prefix' => 'UNIQUE',
            'columns' => []
        ]
    ];

    /**
     * @var array
     */
    public array $afterTableDefinition  = [];

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
    public function getDriverName()
    {
        return $this->driver->getDriverName();
    }


    /**
     * @param string $column
     * @param int $dataType
     * @param int|string|array|null $parameters
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    private function bindColumn(string $column, int $dataType, int|string|array|null $parameters = null): DefinedColumnAccessories
    {
        $this->columns[$column] = [
            'data_type' => $dataType,
            'parameters' => $parameters,
            'accessories' => [
                'nullable' => false
            ]
        ];

        return new DefinedColumnAccessories($column, $this);
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

        //  'DECIMAL'
        //  'DECIMAL({precision}, {scale})'
        //  'VARCHAR({length})'
        //  'DAY({precision} TO MONTH'

        /*
         * DECIMAL{precision_and_scale}
         */

        foreach ($this->columns as $column => $options) {
            $definitionSignature = $this->driver->getDataType($options['data_type']);

            if (!is_null($options['parameters'])) {
                foreach ($options['parameters'] as $parameterKey => $parameterValue) {
                    $definitionSignature = str_replace(
                        '{' . $parameterKey . '}',
                        !is_null($parameterValue) ? '(' . $parameterValue . ')' : '',
                        $definitionSignature
                    );
                }
            }

            $sewedColumns[] = $column . ' ' . $definitionSignature;
        }















        $tableSewedAccessories = [];

        foreach ($this->tableAccessories as $parameters) {
            $accessoryExpression = $parameters;

            if (is_array($parameters)) {
                if (!empty($parameters['columns'])) {
                    if (!empty($parameters['prefix'])) {
                        $accessoryExpression = $parameters['prefix'];
                    }

                    $accessoryExpression .= '(' . implode(', ', $parameters['columns']) . ')';
                } else {
                    continue;
                }
            }

            $tableSewedAccessories[] = $accessoryExpression;
        }

        $sewedColumns[] = implode(', ', $tableSewedAccessories);

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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function boolean(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function bool(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function bit(string $column, int|string $size = 1): DefinedColumnAccessories
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
     * | Only MySQL and MariaDB directly support unsigned types, other drivers  |
     * | simulate unsigned behavior by using CHECK(value > 0) constraint.       |
     * --------------------------------------------------------------------------
     */

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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::TINY_INTEGER,
            [
                'auto_increment' => $autoIncrement,
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedTinyInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function smallInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::SMALL_INTEGER,
            [
                'auto_increment' => $autoIncrement,
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedSmallInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function mediumInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::MEDIUM_INTEGER,
            [
                'auto_increment' => $autoIncrement,
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedMediumInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::INTEGER,
            [
                'auto_increment' => $autoIncrement,
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::BIG_INTEGER,
            [
                'auto_increment' => $autoIncrement,
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function float(string $column, bool $unsigned = false, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::FLOAT,
            compact('unsigned', 'precision')
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedFloat(string $column, int|string|null $precision = null): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function binaryFloat(string $column, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::BINARY_FLOAT,
            compact('unsigned')
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedBinaryFloat(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function double(string $column, bool $unsigned = false, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::DOUBLE,
            compact('unsigned', 'precision')
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedDouble(string $column, int|string|null $precision = null): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function binaryDouble(string $column,  bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::BINARY_DOUBLE,
            compact('unsigned')
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedBinaryDouble(string $column): DefinedColumnAccessories
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
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function decimal(
        string $column,
        bool $unsigned = false,
        int|string|null $precision = null,
        int|string|null $scale = null
    ): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::DECIMAL,
            compact('unsigned', 'precision', 'scale')
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
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * |                                                                        |
     * | Same as "decimal" with the "unsigned" argument specified as "true".    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedDecimal(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnAccessories
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
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function numeric(
        string $column,
        bool $unsigned = false,
        int|string|null $precision = null,
        int|string|null $scale = null
    ): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::NUMERIC,
            compact('unsigned', 'precision', 'scale')
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
     * |                                                                        |
     * | Argument "scale" - represents the number of digits that can be stored  |
     * | to the right of the decimal point.                                     |
     * |     Required - No                                                      |
     * |                                                                        |
     * | Same as "numeric" with the "unsigned" argument specified as "true".    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @param int|string|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedNumeric(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function number(
        string $column,
        bool $unsigned = false,
        int|string|null $precision = null,
        int|string|null $scale = null
    ): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::NUMBER,
            compact('unsigned', 'precision', 'scale')
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedNumber(string $column, int|string|null $precision = null, int|string|null $scale = null): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function real(string $column, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::REAL,
            compact('unsigned')
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedReal(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function smallMoney(string $column, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::SMALL_MONEY,
            compact('unsigned')
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedSmallMoney(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function money(string $column, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::MONEY,
            compact('unsigned')
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedMoney(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function char(string $column, int|string|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::CHAR,
            !is_null($length) ? compact('length') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define n char data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server, Oracle                                                  |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - yes                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function nChar(string $column, int|string|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::N_CHAR,
            !is_null($length) ? compact('length') : null
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function varchar(string $column, int|string|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::VARCHAR,
            !is_null($length) ? compact('length') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define varchar 2 data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - no                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function varchar2(string $column, int|string|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::VARCHAR_2,
            !is_null($length) ? compact('length') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define n varchar data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - no                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function nVarchar(string $column, int|string|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::N_VARCHAR,
            !is_null($length) ? compact('length') : null
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function nVarchar2(string $column, int|string|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::N_VARCHAR_2,
            !is_null($length) ? compact('length') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define tiny text data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function tinyText(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function mediumText(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function text(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function longText(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function nText(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function tinyBlob(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function mediumBlob(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function blob(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function longBlob(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function set(string $column, array $whiteList): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::SET,
            [
                'white_list' => $whiteList
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function enum(string $column, array $whiteList): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::ENUM,
            [
                'white_list' => $whiteList
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function json(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function jsonb(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function binary(string $column, string|int|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::BINARY,
            !is_null($length) ? compact('length') : null
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function varbinary(string $column, string|int|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::VARBINARY,
            !is_null($length) ? compact('length') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define uuid data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function uuid(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function uniqueIdentifier(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function xml(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function xmlType(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function image(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function sqlVariant(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function rowVersion(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function clob(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function nclob(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function raw(string $column, string|int $length): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::RAW,
            !is_null($length) ? compact('length') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define long data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function long(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function urowid(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function bytea(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function hstore(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function inet(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function cidr(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function date(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function dateTime(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function dateTime2(string $column, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::DATE_TIME_2,
            !is_null($precision) ? compact('precision') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define smalldatetime data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function smallDateTime(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function dateTimeOffset(string $column, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::DATE_TIME_OFFSET,
            !is_null($precision) ? compact('precision') : null
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function time(string $column, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::TIME,
            !is_null($precision) ? compact('precision') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define timestamp data type column.                           |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * | Argument "precision" - represents  the precision of the fractional     |
     * | seconds (determines how many digits are stored for the fractional      |
     * | seconds part of the value).                                            |
     * |     Unavailable - MS SQL Server                                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|string|null $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function timestamp(string $column, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::TIMESTAMP,
            !is_null($precision) ? compact('precision') : null
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define year data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function year(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function timeTz(string $column, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::TIME_TZ,
            !is_null($precision) ? compact('precision') : null
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function timeWithTimezone(string $column, int|string|null $precision = null): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function timestampTz(string $column, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::TIMESTAMP_TZ,
            !is_null($precision) ? compact('precision') : null
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function timestampWithTimezone(string $column, int|string|null $precision = null): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function timestampLtz(string $column, int|string|null $precision = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::TIMESTAMP_LTZ,
            !is_null($precision) ? compact('precision') : null
        );
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function timestampWithLocalTimeZone(string $column, int|string|null $precision = null): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function intervalYearToMonth(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function intervalDayToSecond(
        string $column,
        int|string|null $dayPrecision = null,
        int|string|null $secondPrecision = null
    ): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function geometry(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function geometryCollection(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function point(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multipoint(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function line(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function linestring(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multilinestring(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function polygon(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multipolygon(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function geography(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function hierarchyId(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function lSeg(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function lineSegment(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function box(string $column): DefinedColumnAccessories
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
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function circle(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, DataTypes::CIRCLE);
    }
}
