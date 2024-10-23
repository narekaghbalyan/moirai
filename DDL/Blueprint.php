<?php

namespace Moirai\DDL;

use Closure;
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
     * @var int
     */
    private int $defaultStringLength = 255;

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
                    AvailableDbmsDrivers::POSTGRESQL,
                    AvailableDbmsDrivers::MS_SQL_SERVER,
                    AvailableDbmsDrivers::SQLITE]
            )) {
                $parameters[] = 'CHECK (' . $column . ' >= 0)';
            } else {
                $parameters[] = 'UNSIGNED';
            }
        }

        if ($autoIncrement) {
            $parameters[] = 'AUTO_INCREMENT';
        }

        return $parameters;
    }

    public function floatBaseBinder(string $dataType,
                                    string $column,
                                    int|null $total = null,
                                    int|null $places = null,
                                    bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = [];

        if (!is_null($total) && !is_null($places)) {
            $parameters[] = '(' . $total . ', ' . $places . ')';
        }

        $parameters = array_merge(
            $parameters,
            $this->resolveAutoIncrementAndUnsignedParametersUsing(false, $unsigned)
        );

        return $this->bindColumn($column, $this->driver->getDataType($dataType), $parameters);
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

        return new DefinedColumnAccessories($column, $this);
    }




























    /**
     * --------------------------------------------------------------------------
     * | Clause to define boolean data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL                                                             |
     * | ---------------------------------------------------------------------- |
     * | It can store 0 or 1.                                                   |
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
     * | It can store 0 or 1.                                                   |
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
     * | Size parameters works only for MySQL and MariaDB, it can be in         |
     * | interval from 1 to 64. In MS SQL Server bit is a logical type and can  |
     * | be 0, 1 or NULL (can not take size parameter, if you pass that for     |
     * | MS SQL Server, parameter will be ignored).                             |
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
     * | Clause to define tiny integer data type column.                        |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * | If driver is MS SQL Server parameter "unsigned" will be ignored,       |
     * | because MS SQL Server not support "unsigned" parameter, tiny integer   |
     * | is unsigned by default.                                                |
     * | For MS SQL Server it can store values from 0 to 255 and for other      |
     * | drivers it can store values from -128 to 127 (or 0 to 255 if           |
     * | unsigned).                                                             |
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
            $this->resolveParametersUsing(
                $column,
                $autoIncrement,
                $this->getDriverName() !== AvailableDbmsDrivers::MS_SQL_SERVER ? $unsigned : false
            )
        );
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define small integer data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * | If driver is Postgre SQL or MS SQL Server and parameter "unsigned" is  |
     * | true unsigned will work by using CHECK(value >= 0), because these      |
     * | drivers do not have unsigned behavior supporting, so statement will    |
     * | simulate that behavior by using "CHECK". And for this reason, the      |
     * | upper limit of the value does not change (remains 32767), we only      |
     * | include a check that the number is not negative. For other drivers the |
     * | upper limit increases (becomes 65535). For all drivers the lower limit |
     * | will be 0 if "unsigned" parameter is true.                             |
     * | It can store values from -32768 to 32767 (or 0 to 65535 if unsigned    |
     * | except MS SQL Server, for MS SQL Server unsigned values can be from 0  |
     * | to 32767).                                                             |
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
     * | Clause to define medium integer data type column.                      |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * | It can store values from -8388608 to 8388607 (or 0 to 16777215 if      |
     * | unsigned).                                                             |
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
     * | Clause to define integer data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | If driver is Postgre SQL or MS SQL Server or SQLite and parameter      |
     * | "unsigned" is true unsigned will work by using CHECK(value >= 0),      |
     * | because these drivers do not have unsigned behavior supporting, so     |
     * | statement will simulate that behavior by using "CHECK". And for this   |
     * | reason, the upper limit of the value does not change (remains          |
     * | 2147483647), we only include a check that the number is not negative.  |
     * | For other drivers the upper limit increases (becomes 4294967295). For  |
     * | all drivers the lower limit will be 0 if "unsigned" parameter is true. |
     * | It can store values from -2147483648 to 2147483647 (or 0 to 4294967295 |
     * | if unsigned except MS SQL Server and Postgre SQL. For these drivers    |
     * | unsigned values can be from 0 to 2147483647) except SQLite, for SQLite |
     * | it can store from -9223372036854775808 to 9223372036854775807 (or 0    |
     * | to 9223372036854775807 if unsigned).                                   |
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
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveParametersUsing($autoIncrement, $unsigned);

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::BIG_INTEGER), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedTinyInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::TINY_INTEGER), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedSmallInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::SMALL_INTEGER), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedMediumInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::MEDIUM_INTEGER), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::INTEGER), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::BIG_INTEGER), $parameters);
    }

    /**
     * @param string $column
     * @param int $total
     * @param int $places
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function float(string $column, int $total = 8, int $places = 2, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::FLOAT, $column, $total, $places, $unsigned);
    }

    /**
     * @param string $column
     * @param int $total
     * @param int $places
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function binaryFloat(string $column, int $total = 8, int $places = 2, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::BINARY_FLOAT, $column, $total, $places, $unsigned);
    }

    /**
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function double(string $column, int|null $total = null, int|null $places = null, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DOUBLE, $column, $total, $places, $unsigned);
    }

    /**
     * @param string $column
     * @param int $total
     * @param int $places
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function decimal(string $column, int $total = 8, int $places = 2, bool $unsigned = false): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DECIMAL, $column, $total, $places, $unsigned);
    }

    /**
     * @param string $column
     * @param int $total
     * @param int $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function unsignedFloat(string $column, int $total = 8, int $places = 2): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::FLOAT, $column, $total, $places, true);
    }

    /**
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function unsignedDouble(string $column, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DOUBLE, $column, $total, $places, true);
    }

    /**
     * @param string $column
     * @param int $total
     * @param int $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function unsignedDecimal(string $column, int $total = 8, int $places = 2): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DECIMAL, $column, $total, $places, true);
    }





























    /**
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function char(string $column, string|int|null $length = null): DefinedColumnAccessories
    {
        $length = $length ?? $this->defaultStringLength;

        return $this->bindColumn($column, $this->driver->getDataType(DataTypes::CHAR), compact('length'));
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
