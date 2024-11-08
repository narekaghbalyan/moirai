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

        $this->sewDefinedColumns();
    }

    /**
     * @return string
     */
    public function getDriverName()
    {
        return $this->driver->getDriverName();
    }

    /**
     * @return string
     */
    private function sewDefinedColumns(): string
    {
        if (empty($this->columns)) {
            return '';
        }

        $sewedColumns = [];

        foreach ($this->columns as $column => $parameters) {
            $sewedColumns[] = $column . ' ' . implode(' ', $parameters);
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



    private function resolveParametersUsing(string $column, bool $autoIncrement, bool $unsigned): array
    {
        $parameters = [];

        if ($unsigned) {
            if (in_array(
                $this->getDriverName(),
                [
                    AvailableDbmsDrivers::MYSQL,
                    AvailableDbmsDrivers::MARIADB
                ]
            )) {
                $parameters[] = 'UNSIGNED';
            } else {
                $parameters[] = 'CHECK (' . $column . ' >= 0)';
            }
        }

        if ($autoIncrement) {
            $parameters[] = 'AUTO_INCREMENT';
        }

        return $parameters;
    }

    /**
     * @param string $dataType
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function floatBaseBinder(string $dataType,
                                    string $column,
                                    int|null $total = null,
                                    int|null $places = null,
                                    bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = [];

        if (!is_null($total)) {
            $parameters = '(' . $total;

            if (!is_null($places)) {
                $parameters .= ', ' . $places;
            }

            $parameters = [$parameters . ')'];
        }

        $parameters = array_merge(
            $parameters,
            $this->resolveParametersUsing($column, false, $unsigned)
        );

        return $this->bindColumn($column, $dataType, $parameters);
    }


    /**
     * @param string $column
     * @param string $dataType
     * @param array $parameters
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    private function bindColumn(string $column, string $dataType, array $parameters = []): DefinedColumnAccessories
    {
        $this->columns[$column] = array_merge(compact('dataType'), $parameters);
        $this->columns[$column]['value'] = 'NOT NULL';

//        $this->columns[$column] = [
//            'data_type' => $dataType,
//            'parameters' => $parameters
//        ]

        return new DefinedColumnAccessories($column, $this);
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
     * @param int $size
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function bit(string $column, int $size = 1): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::BIT,
            in_array($this->getDriverName(), [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])
                ? ['(' . $size . ')']
                : []
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
            $this->resolveParametersUsing($column, $autoIncrement, $unsigned)
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
            $this->resolveParametersUsing($column, $autoIncrement, $unsigned)
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
            $this->resolveParametersUsing($column, $autoIncrement, $unsigned)
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
            $this->resolveParametersUsing($column, $autoIncrement, $unsigned)
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
            $this->resolveParametersUsing($column, $autoIncrement, $unsigned)
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
     * @param int|null $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function float(string $column, bool $unsigned = false, int|null $precision = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::FLOAT, $column, $precision, null, $unsigned);
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
     * @param int|null $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedFloat(string $column, int|null $precision = null): DefinedColumnAccessories
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
        return $this->floatBaseBinder(DataTypes::BINARY_FLOAT, $column, null, null, $unsigned);
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
     * @param int|null $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function double(string $column,  bool $unsigned = false, int|null $precision = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DOUBLE, $column, $precision, null, $unsigned);
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
     * @param int|null $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedDouble(string $column, int|null $precision = null): DefinedColumnAccessories
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
        return $this->floatBaseBinder(DataTypes::BINARY_DOUBLE, $column, null, null, $unsigned);
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
     * @param int|null $precision
     * @param int|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function decimal(string $column, bool $unsigned = false, int|null $precision = null, int|null $scale = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DECIMAL, $column, $precision, $scale, $unsigned);
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
     * @param int|null $precision
     * @param int|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedDecimal(string $column, int|null $precision = null, int|null $scale = null): DefinedColumnAccessories
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
     * @param int|null $precision
     * @param int|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function numeric(string $column, bool $unsigned = false, int|null $precision = null, int|null $scale = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::NUMERIC, $column, $precision, $scale, $unsigned);
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
     * @param int|null $precision
     * @param int|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedNumeric(string $column, int|null $precision = null, int|null $scale = null): DefinedColumnAccessories
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
     * @param int|null $precision
     * @param int|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function number(string $column, bool $unsigned = false, int|null $precision = null, int|null $scale = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::NUMBER, $column, $precision, $scale, $unsigned);
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
     * @param int|null $precision
     * @param int|null $scale
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedNumber(string $column, int|null $precision = null, int|null $scale = null): DefinedColumnAccessories
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
        return $this->floatBaseBinder(DataTypes::REAL, $column, null, null, $unsigned);
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
        return $this->floatBaseBinder(DataTypes::SMALL_MONEY, $column, null, null, $unsigned);
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
        return $this->floatBaseBinder(DataTypes::MONEY, $column, null, null, $unsigned);
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
    public function char(string $column, string|int|null $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::CHAR,
            !is_null($length) ? [$length] : []
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define n_char data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server, Oracle                                                  |
     * | ---------------------------------------------------------------------- |
     * | Argument "length" - represents length.                                 |
     * |     Required - no                                                      |
     * |     Default - for all drivers "defaultStringLength", for SQLite there  |
     * |               is no default value                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function nChar(string $column, string|int $length = null): DefinedColumnAccessories
    {
        return $this->bindColumn(
            $column,
            DataTypes::N_CHAR,
            !is_null($length) ? [$length] : []
        );
    }

    /**
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function varchar(string $column, string|int|null $length = null): DefinedColumnAccessories
    {
        $length = $length ?? $this->defaultStringLength;

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::VARCHAR), compact('length'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function tinyText(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::TINY_TEXT));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function text(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::TEXT));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function mediumText(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::MEDIUM_TEXT));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function longText(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::LONG_TEXT));
    }



    /**
     * @param string $column
     * @param array $whiteList
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function enum(string $column, array $whiteList): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::ENUM),
            [implode(', ', $whiteList)]
        );
    }

    /**
     * @param string $column
     * @param array $whiteList
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function set(string $column, array $whiteList): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::SET),
            [implode(', ', $whiteList)]
        );
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function json(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::JSON));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function jsonb(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::JSONB));
    }
















    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function date(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::DATE));
    }

    /**
     * @param string $column
     * @param int $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function dateTime(string $column, int $precision = 0): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::DATE_TIME), ['(' . $precision . ')']);
    }

    /**
     * @param string $column
     * @param int $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function time(string $column, int $precision = 0): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::TIME), ['(' . $precision . ')']);
    }

    /**
     * @param string $column
     * @param int $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function timestamp(string $column, int $precision = 0): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::TIMESTAMP), ['(' . $precision . ')']);
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function year(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::YEAR));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function binary(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::BINARY));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function varbinary(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::VARBINARY));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function geometry(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::GEOMETRY));
    }

    /**
     * @param string $column
     * @param int|string|null $srid
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function point(string $column, null|int|string $srid = null): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::POINT), compact('srid'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function lineString(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::LINE_STRING));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function polygon(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::POLYGON));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multipoint(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::MULTI_POINT));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multiLineString(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::MULTI_LINE_STRING));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multiPolygon(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::MULTI_POLYGON));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function geometryCollection(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::GEOMETRY_COLLECTION));
    }
}
