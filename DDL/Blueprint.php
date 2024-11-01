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

    /**
     * @throws \Exception
     */
    public function floatBaseBinder(string $dataType,
                                    string $column,
                                    int|null $total = null,
                                    int|null $places = null,
                                    bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = [];

        if (!is_null($total)) {
            if (in_array($this->getDriverName(), [AvailableDbmsDrivers::MS_SQL_SERVER, AvailableDbmsDrivers::ORACLE])) {
                if ($total < 1 || $total > 53) {
                    throw new Exception(
                        'For float type in DBMS driver MS SQL Server, argument "total" (which means size of float)
                        must be in the range from 1 to 53.'
                    );
                }
            }

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
     * |                                                                        |
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
     * | "size" parameters works only for MySQL and MariaDB, it can be in       |
     * | interval from 1 to 64.                                                 |
     * |                                                                        |
     * | In MS SQL Server "bit" is a logical type and can                       |
     * | be 0, 1 or NULL (can not take "size" parameter, if you pass that for   |
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
     * |                                                                        |
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
     * |                                                                        |
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
     * | 2147483647 for Postgre SQL and MS SQL Server and for SQLite remains    |
     * | 9223372036854775807), we only include a check that the number is not   |
     * | negative. For other drivers the upper limit increases (becomes         |
     * | 4294967295). For all drivers the lower limit will be 0 if "unsigned"   |
     * | parameter is true.                                                     |
     * |                                                                        |
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
     * --------------------------------------------------------------------------
     * | Clause to define big integer data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * | If driver is Postgre SQL or MS SQL Server and parameter "unsigned" is  |
     * | true unsigned will work by using CHECK(value >= 0), because these      |
     * | drivers do not have unsigned behavior supporting, so statement will    |
     * | simulate that behavior by using "CHECK". And for this reason, the      |
     * | upper limit of the value does not change (remains                      |
     * | 9223372036854775807), we only include a check that the number is not   |
     * | negative. For other drivers the upper limit increases (becomes         |
     * | 18446744073709551615). For all drivers the lower limit will be 0 if    |
     * | "unsigned" parameter is true.                                          |
     * |                                                                        |
     * | It can store values from -9223372036854775808 to 9223372036854775807   |
     * | (or 0 to 18446744073709551615 if unsigned except Postgre SQL and MS    |
     * | SQL Server, for these drivers unsigned values can be from 0 to         |
     * | 9223372036854775807).                                                  |
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
     * | Clause to define unsigned tiny integer data type column.               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, MS SQL Server                                          |
     * | ---------------------------------------------------------------------- |
     * | It can store values from 0 to 255.                                     |
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
     * | Clause to define unsigned small integer data type column.              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * | If driver is Postgre SQL or MS SQL Server unsigned will work by using  |
     * | CHECK(value >= 0), because these drivers do not have unsigned behavior |
     * | supporting, so statement will simulate that behavior by using "CHECK". |
     * | And for this reason, the upper limit of the value does not change      |
     * | (remains 32767), we only include a check that the number is not        |
     * | negative. For other drivers the upper limit increases (becomes 65535). |
     * | For all drivers the lower limit will be 0.                             |
     * |                                                                        |
     * | It can store values from 0 to 65535 except MS SQL Server, for MS SQL   |
     * | Server unsigned values can be from 0 to 32767).                        |
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
     * | Clause to define unsigned medium integer data type column.             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * | It can store values from 0 to 16777215.                                |
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
     * | Clause to define unsigned integer data type column.                    |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server, SQLite                     |
     * | ---------------------------------------------------------------------- |
     * | If driver is Postgre SQL or MS SQL Server or SQLite unsigned will work |
     * | by using CHECK(value >= 0), because these drivers do not have unsigned |
     * | behavior supporting, so statement will simulate that behavior by using |
     * | "CHECK". And for this reason, the upper limit of the value does not    |
     * | change (remains 2147483647 for Postgre SQL and MS SQL Server and for   |
     * | SQLite remains 9223372036854775807), we only include a check that the  |
     * | number is not negative. For other drivers the upper limit increases    |
     * | (becomes 4294967295). For all drivers the lower limit will be 0.       |
     * |                                                                        |
     * | It can store values from 0 to 4294967295 except MS SQL Server and      |
     * | Postgre SQL. For Postgre SQL and MS SQL server drivers values can be   |
     * | from 0 to 2147483647. For SQLite it can store from 0 to                |
     * | 9223372036854775807.                                                   |
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
     * | Clause to define unsigned big integer data type column.                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, Postgre SQL, MS SQL Server                             |
     * | ---------------------------------------------------------------------- |
     * | If driver is Postgre SQL or MS SQL Server unsigned will work by using  |
     * | CHECK(value >= 0), because these drivers do not have unsigned behavior |
     * | supporting, so statement will simulate that behavior by using "CHECK". |
     * | And for this reason, the upper limit of the value does not change      |
     * | (remains 9223372036854775807), we only include a check that the number |
     * | is not negative. For other drivers the upper limit increases (becomes  |
     * | 18446744073709551615). For all drivers the lower limit will be.        |
     * |                                                                        |
     * | It can store values from -9223372036854775808 to 9223372036854775807   |
     * | (or 0 to 18446744073709551615 if unsigned except Postgre SQL and MS    |
     * | SQL Server, for these drivers unsigned values can be from 0 to         |
     * | 9223372036854775807).                                                  |
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
     * | MySQL, MariaDB, MS SQL Server, Oracle                                  |
     * | ---------------------------------------------------------------------- |
     * | If driver is MS SQL Server you cannot specify "total" and "places".    |
     * | The "float" type in MS SQL Server is an approximate numeric data type. |
     * | If you need exact numeric precision, it is recommended to use the      |
     * | "decimal" or "numeric" types instead, where you can specify both       |
     * | precision and scale. Instead of specifying "total" and "places" in MS  |
     * | SQL Server you can specify storage size (in third ("total") argument). |
     * | It can store values from 1 to 54, if you specify any other value,      |
     * | exception will be thrown.                                              |
     * | MS SQL Server size argument:                                           |
     * | from 1 to 24 - 4 bytes (single precision)                              |
     * | from 25 to 53 - 8 bytes (double precision)                             |
     * | by default 8 bytes (double precision)                                  |
     * | For MS SQL Server if you specify argument "places" it will be ignored. |
     * |                                                                        |
     * | If driver is Oracle you can not specify "total" and "places". The      |
     * | "float" type in Oracle behaves as a subtype of "number" type, and when |
     * | you declare a column as "float", it defaults to a floating-point       |
     * | representation without the ability to specify arguments for precision  |
     * | or scale. If you want to specify "total" and "places", you need to use |
     * | the "number" data type instead.                                        |
     * |                                                                        |
     * | If driver is MS SQL Server or Oracle and parameter "unsigned" is true  |
     * | unsigned will work by using CHECK(value >= 0), because these drivers   |
     * | do not have unsigned behavior supporting, so statement will simulate   |
     * | that behavior by using "CHECK".                                        |
     * |                                                                        |
     * | For other drivers:                                                     |
     * | You can specify "total" and "places".                                  |
     * | Argument total - the maximum number of digits (the precision).         |
     * | Argument places - the number of digits to the right of the decimal     |
     * | point (the scale).                                                     |
     * |                                                                        |
     * | You can specify "total" and "places" or only "total", but can not      |
     * | specify only "places", if you do that, nothing changes, you must       |
     * | specify "total" if you want to specify "places".                       |
     * |                                                                        |
     * | Storage size of the "float" data type depends on driver and on whether |
     * | you specify or not specify arguments.                                  |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|null $total
     * @param bool $unsigned
     * @param int|null $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function float(string $column, bool $unsigned = false, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::FLOAT, $column, $total, $places, $unsigned);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define binary float data type column.                        |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | If "unsigned" is true, that will work by using CHECK(value >= 0),      |
     * | because this driver do not have unsigned behavior supporting, so       |
     * | statement will simulate that behavior by using "CHECK".                |
     * |                                                                        |
     * | Binary float always uses 4 bytes and provides single-precision         |
     * | floating-point representation. It provides approximately about 6 to 7  |
     * | decimal digits of precision. It have range approximately from          |
     * | -3.4028235E+38 to 3.4028235E+38.                                       |
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
     * | Clause to define double data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariaDB, PostgreSQL                                             |
     * | ---------------------------------------------------------------------- |
     * | If driver is PostgreSQL you can not specify "total" and "places". In   |
     * | PostgreSQL, the "double" ("double precision") type is always a         |
     * | floating-point type that uses 8 bytes of storage and provides a large  |
     * | range of values with a certain level of precision (approximately 15    |
     * | decimal digits).                                                       |
     * | If you need exact numeric precision, it is recommended to use the      |
     * | "numeric" type instead, where you can specify both precision and       |
     * | scale.                                                                 |
     * |                                                                        |
     * | If driver is PostgreSQL and "unsigned" is true, that will work by      |
     * | using CHECK(value >= 0), because PostgreSQL driver do not have         |
     * | unsigned behavior supporting, so statement will simulate that behavior |
     * | by using "CHECK".                                                      |
     * |                                                                        |
     * | Storage size of the "float" data type depends on driver and on whether |
     * | you specify or not specify arguments.                                  |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function double(string $column,  bool $unsigned = false, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DOUBLE, $column, $total, $places, $unsigned);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define binary double data type column.                       |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | ----------------------------- Oracle ------------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 65). It is required parameter.         |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)). If not           |    |
     * | | specified, the default scale is 0, meaning it will store only   |    |
     * | | whole numbers.                                                  |    |
     * | |                                                                 |    |
     * | | Storage size - 8 bytes (fixed-point).                           |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -1.7976931348623157E+308 to               |    |
     * | | 1.7976931348623157E+308.                                        |    |
     * | | Range (unsigned) - From 0 to 1.7976931348623157E+308 (simulated |    |
     * | | using constraints).                                             |    |
     * | |                                                                 |    |
     * | | Precision - Up to 15 decimal places.                            |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | Binary float always uses 8 bytes and provides double-precision         |
     * | floating-point representation. It provides approximately 15 decimal    |
     * | digits of precision It have range approximately from                   |
     * | -1.7976931348623157E+308 to 1.7976931348623157E+308.                   |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function binaryDouble(string $column,  bool $unsigned = false, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::BINARY_DOUBLE, $column, $total, $places, $unsigned);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define decimal data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MySQL, MariDB, PostgreSQL, MS SQL Server, SQLite                       |
     * | ---------------------------------------------------------------------- |
     * | ------------------------- MySQL, Maria DB -------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 65). It is required parameter.         |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)). If not           |    |
     * | | specified, the default scale is 0, meaning it will store only   |    |
     * | | whole numbers.                                                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 20 bytes) (fixed-point).         |    |
     * | | 1 to 9 digits - 4 bytes                                         |    |
     * | | 10 to 19 digits - 8 bytes                                       |    |
     * | | 20 to 29 digits - 12 bytes                                      |    |
     * | | 30 to 39 digits - 16 bytes                                      |    |
     * | | 40 to 65 digits - 20 bytes                                      |    |
     * | |                                                                 |    |
     * | | Range (signed) - Variable (depend on the combination of         |    |
     * | | precision and scale).                                           |    |
     * | | Range (unsigned) - Start from 0 and max value is variable       |    |
     * | | (depend on the combination of precision and scale).             |    |
     * | |                                                                 |    |
     * | | Unsigned support - These drivers support unsigned types         |    |
     * | | directly.                                                       |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | --------------------------- PostgreSQL ----------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 1000). It is not required parameter.   |    |
     * | | By default no explicit limit on the total number of digits,     |    |
     * | | it can handle a very large number of digits.                    |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)). If not           |    |
     * | | specified, the default scale is 0, meaning it will store only   |    |
     * | | whole numbers.                                                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 100 bytes) (fixed-point).        |    |
     * | | 1 to 17 digits - 2 bytes                                        |    |
     * | | 18 to 38 digits - 4 bytes                                       |    |
     * | | 39 to 57 digits - 6 bytes                                       |    |
     * | | 58 to 76 digits - 8 bytes                                       |    |
     * | | 77 to 93 digits - 10 bytes                                      |    |
     * | | 94 to 111 digits - 12 bytes                                     |    |
     * | | 112 to 128 digits - 14 bytes                                    |    |
     * | | 129 to 145 digits - 16 bytes                                    |    |
     * | | 146 to 162 digits - 18 bytes                                    |    |
     * | | 163 to 179 digits - 20 bytes                                    |    |
     * | | 180 to 196 digits - 22 bytes                                    |    |
     * | | 197 to 213 digits - 24 bytes                                    |    |
     * | | 214 to 230 digits - 26 bytes                                    |    |
     * | | 231 to 247 digits - 28 bytes                                    |    |
     * | | 248 to 264 digits - 30 bytes                                    |    |
     * | | 265 to 281 digits - 32 bytes                                    |    |
     * | | 282 to 298 digits - 34 bytes                                    |    |
     * | | 299 to 315 digits - 36 bytes                                    |    |
     * | | 316 to 332 digits - 38 bytes                                    |    |
     * | | 333 to 349 digits - 40 bytes                                    |    |
     * | | 350 to 366 digits - 42 bytes                                    |    |
     * | | 367 to 383 digits - 44 bytes                                    |    |
     * | | 384 to 400 digits - 46 bytes                                    |    |
     * | | 401 to 417 digits - 48 bytes                                    |    |
     * | | 418 to 434 digits - 50 bytes                                    |    |
     * | | 435 to 451 digits - 52 bytes                                    |    |
     * | | 452 to 468 digits - 54 bytes                                    |    |
     * | | 469 to 485 digits - 56 bytes                                    |    |
     * | | 486 to 502 digits - 58 bytes                                    |    |
     * | | 503 to 519 digits - 60 bytes                                    |    |
     * | | 520 to 536 digits - 62 bytes                                    |    |
     * | | 537 to 553 digits - 64 bytes                                    |    |
     * | | 554 to 570 digits - 66 bytes                                    |    |
     * | | 571 to 587 digits - 68 bytes                                    |    |
     * | | 588 to 604 digits - 70 bytes                                    |    |
     * | | 605 to 621 digits - 72 bytes                                    |    |
     * | | 622 to 638 digits - 74 bytes                                    |    |
     * | | 639 to 655 digits - 76 bytes                                    |    |
     * | | 656 to 672 digits - 78 bytes                                    |    |
     * | | 673 to 689 digits - 80 bytes                                    |    |
     * | | 690 to 706 digits - 82 bytes                                    |    |
     * | | 707 to 723 digits - 84 bytes                                    |    |
     * | | 724 to 740 digits - 86 bytes                                    |    |
     * | | 741 to 757 digits - 88 bytes                                    |    |
     * | | 758 to 774 digits - 90 bytes                                    |    |
     * | | 775 to 791 digits - 92 bytes                                    |    |
     * | | 792 to 808 digits - 94 bytes                                    |    |
     * | | 809 to 825 digits - 96 bytes                                    |    |
     * | | 826 to 842 digits - 98 bytes                                    |    |
     * | | 843 to 859 digits - 100 bytes                                   |    |
     * | |                                                                 |    |
     * | | Range (signed) - Variable (depend on the combination of         |    |
     * | | precision and scale).                                           |    |
     * | | Range (unsigned) - Start from 0 and max value is variable       |    |
     * | | (depend on the combination of precision and scale) (simulated   |    |
     * | | using constraints).                                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | -------------------------- MS SQL Server --------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 38). It is not required parameter. If  |    |
     * | | not specified, the default value is 18.                         |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)). If not           |    |
     * | | specified, the default scale is 0, meaning it will store only   |    |
     * | | whole numbers.                                                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 16 bytes) (fixed-point).         |    |
     * | | 1 to 9 digits - 4 bytes                                         |    |
     * | | 10 to 19 digits - 8 bytes                                       |    |
     * | | 20 to 28 digits - 12 bytes                                      |    |
     * | | 29 to 38 digits - 16 bytes                                      |    |
     * | |                                                                 |    |
     * | | Range (signed) - Variable (depend on the combination of         |    |
     * | | precision and scale).                                           |    |
     * | | Range (unsigned) - Start from 0 and max value is variable       |    |
     * | | (depend on the combination of precision and scale) (simulated   |    |
     * | | using constraints).                                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | ------------------------------ SQLite -----------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 8). It is not required parameter.      |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)).                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 8 bytes) (fixed-point).          |    |
     * | | Depending on the actual numeric value being stored.             |    |
     * | | From -127 to 127 - 1 byte                                       |    |
     * | | From -32,767 to 32,767 - 2 byte                                 |    |
     * | | From -8,388,608 to 8,388,608 - 3 byte                           |    |
     * | | From -2,147,483,648 to 2,147,483,648 - 4 byte                   |    |
     * | | Larger numbers and floating-point values - 8 byte               |    |
     * | | Very large integers or decimals beyond standard ranges -        |    |
     * | | variable length                                                 |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -1.7976931348623157E+308 to               |    |
     * | | 1.7976931348623157E+308.                                        |    |
     * | | Range (unsigned) - From 0 to 1.7976931348623157E+308.           |    |
     * | | (simulated using constraints).                                  |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @param int|null $total
     * @param int|null $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function decimal(string $column, bool $unsigned = false, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::DECIMAL, $column, $total, $places, $unsigned);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define numeric data type column.                             |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | ------------------------- MySQL, Maria DB -------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 65). It is required parameter.         |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)). If not           |    |
     * | | specified, the default scale is 0, meaning it will store only   |    |
     * | | whole numbers.                                                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 20 bytes) (fixed-point).         |    |
     * | | 1 to 9 digits - 4 bytes                                         |    |
     * | | 10 to 19 digits - 8 bytes                                       |    |
     * | | 20 to 29 digits - 12 bytes                                      |    |
     * | | 30 to 39 digits - 16 bytes                                      |    |
     * | | 40 to 65 digits - 20 bytes                                      |    |
     * | |                                                                 |    |
     * | | Range (signed) - Variable (depend on the combination of         |    |
     * | | precision and scale).                                           |    |
     * | | Range (unsigned) - Start from 0 and max value is variable       |    |
     * | | (depend on the combination of precision and scale).             |    |
     * | |                                                                 |    |
     * | | Unsigned support - These drivers support unsigned types         |    |
     * | | directly.                                                       |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | --------------------------- PostgreSQL ----------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 1000). It is not required parameter.   |    |
     * | | By default no explicit limit on the total number of digits,     |    |
     * | | it can handle a very large number of digits.                    |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)). If not           |    |
     * | | specified, the default scale is 0, meaning it will store only   |    |
     * | | whole numbers.                                                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 100 bytes) (fixed-point).        |    |
     * | | 1 to 17 digits - 2 bytes                                        |    |
     * | | 18 to 38 digits - 4 bytes                                       |    |
     * | | 39 to 57 digits - 6 bytes                                       |    |
     * | | 58 to 76 digits - 8 bytes                                       |    |
     * | | 77 to 93 digits - 10 bytes                                      |    |
     * | | 94 to 111 digits - 12 bytes                                     |    |
     * | | 112 to 128 digits - 14 bytes                                    |    |
     * | | 129 to 145 digits - 16 bytes                                    |    |
     * | | 146 to 162 digits - 18 bytes                                    |    |
     * | | 163 to 179 digits - 20 bytes                                    |    |
     * | | 180 to 196 digits - 22 bytes                                    |    |
     * | | 197 to 213 digits - 24 bytes                                    |    |
     * | | 214 to 230 digits - 26 bytes                                    |    |
     * | | 231 to 247 digits - 28 bytes                                    |    |
     * | | 248 to 264 digits - 30 bytes                                    |    |
     * | | 265 to 281 digits - 32 bytes                                    |    |
     * | | 282 to 298 digits - 34 bytes                                    |    |
     * | | 299 to 315 digits - 36 bytes                                    |    |
     * | | 316 to 332 digits - 38 bytes                                    |    |
     * | | 333 to 349 digits - 40 bytes                                    |    |
     * | | 350 to 366 digits - 42 bytes                                    |    |
     * | | 367 to 383 digits - 44 bytes                                    |    |
     * | | 384 to 400 digits - 46 bytes                                    |    |
     * | | 401 to 417 digits - 48 bytes                                    |    |
     * | | 418 to 434 digits - 50 bytes                                    |    |
     * | | 435 to 451 digits - 52 bytes                                    |    |
     * | | 452 to 468 digits - 54 bytes                                    |    |
     * | | 469 to 485 digits - 56 bytes                                    |    |
     * | | 486 to 502 digits - 58 bytes                                    |    |
     * | | 503 to 519 digits - 60 bytes                                    |    |
     * | | 520 to 536 digits - 62 bytes                                    |    |
     * | | 537 to 553 digits - 64 bytes                                    |    |
     * | | 554 to 570 digits - 66 bytes                                    |    |
     * | | 571 to 587 digits - 68 bytes                                    |    |
     * | | 588 to 604 digits - 70 bytes                                    |    |
     * | | 605 to 621 digits - 72 bytes                                    |    |
     * | | 622 to 638 digits - 74 bytes                                    |    |
     * | | 639 to 655 digits - 76 bytes                                    |    |
     * | | 656 to 672 digits - 78 bytes                                    |    |
     * | | 673 to 689 digits - 80 bytes                                    |    |
     * | | 690 to 706 digits - 82 bytes                                    |    |
     * | | 707 to 723 digits - 84 bytes                                    |    |
     * | | 724 to 740 digits - 86 bytes                                    |    |
     * | | 741 to 757 digits - 88 bytes                                    |    |
     * | | 758 to 774 digits - 90 bytes                                    |    |
     * | | 775 to 791 digits - 92 bytes                                    |    |
     * | | 792 to 808 digits - 94 bytes                                    |    |
     * | | 809 to 825 digits - 96 bytes                                    |    |
     * | | 826 to 842 digits - 98 bytes                                    |    |
     * | | 843 to 859 digits - 100 bytes                                   |    |
     * | |                                                                 |    |
     * | | Range (signed) - Variable (depend on the combination of         |    |
     * | | precision and scale).                                           |    |
     * | | Range (unsigned) - Start from 0 and max value is variable       |    |
     * | | (depend on the combination of precision and scale) (simulated   |    |
     * | | using constraints).                                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | -------------------------- MS SQL Server --------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 38). It is not required parameter. If  |    |
     * | | not specified, the default value is 18.                         |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)). If not           |    |
     * | | specified, the default scale is 0, meaning it will store only   |    |
     * | | whole numbers.                                                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 16 bytes) (fixed-point).         |    |
     * | | 1 to 9 digits - 4 bytes                                         |    |
     * | | 10 to 19 digits - 8 bytes                                       |    |
     * | | 20 to 28 digits - 12 bytes                                      |    |
     * | | 29 to 38 digits - 16 bytes                                      |    |
     * | |                                                                 |    |
     * | | Range (signed) - Variable (depend on the combination of         |    |
     * | | precision and scale).                                           |    |
     * | | Range (unsigned) - Start from 0 and max value is variable       |    |
     * | | (depend on the combination of precision and scale) (simulated   |    |
     * | | using constraints).                                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | ------------------------------ SQLite -----------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (from 1 to 8). It is not required parameter.      |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point (from 0 to up to the   |    |
     * | | specified precision (argument "total" value)).                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 8 bytes) (fixed-point).          |    |
     * | | Depending on the actual numeric value being stored.             |    |
     * | | From -127 to 127 - 1 byte                                       |    |
     * | | From -32,767 to 32,767 - 2 byte                                 |    |
     * | | From -8,388,608 to 8,388,608 - 3 byte                           |    |
     * | | From -2,147,483,648 to 2,147,483,648 - 4 byte                   |    |
     * | | Larger numbers and floating-point values - 8 byte               |    |
     * | | Very large integers or decimals beyond standard ranges -        |    |
     * | | variable length                                                 |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -1.7976931348623157E+308 to               |    |
     * | | 1.7976931348623157E+308.                                        |    |
     * | | Range (unsigned) - From 0 to 1.7976931348623157E+308.           |    |
     * | | (simulated using constraints).                                  |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | Same as "decimal".                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @param int|null $total
     * @param int|null $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function numeric(string $column, bool $unsigned = false, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->decimal($column, $unsigned, $total, $places);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define number data type column.                              |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | Oracle                                                                 |
     * | ---------------------------------------------------------------------- |
     * | ------------------------------ Oracle -----------------------------    |
     * | | Argument "total" - Represents the total number of digits that   |    |
     * | | can be stored (up to 38). It is not required parameter. If not  |    |
     * | | specified, the default value is 38.                             |    |
     * | |                                                                 |    |
     * | | Argument "places" - Represents the number of digits that can be |    |
     * | | stored to the right of the decimal point. It is not required    |    |
     * | | parameter. If not specified, the default scale is 0, meaning it |    |
     * | | will store only whole numbers.                                  |    |
     * | |                                                                 |    |
     * | | Storage size - Variable (up to 22 bytes) (fixed-point).         |    |
     * | | 0 to 9 digits - 1 byte                                          |    |
     * | | 10 to 18 digits - 2 bytes                                       |    |
     * | | 19 to 38 digits - 3 to 22 bytes (depending on the precision)    |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -10^125 to 10^125.                        |    |
     * | | Range (unsigned) - From 0 to 10^125 (simulated using            |    |
     * | | constraints).                                                   |    |
     * | |                                                                 |    |
     * | | Precision - Up to 38 digits.                                    |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param bool $unsigned
     * @param int|null $total
     * @param int|null $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function number(string $column, bool $unsigned = false, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder(DataTypes::NUMBER, $column, $total, $places, $unsigned);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define real data type column.                                |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server, SQLite                                      |
     * | ---------------------------------------------------------------------- |
     * | ---------------------------- PostgreSQL ---------------------------    |
     * | | Storage size - 4 bytes (single-precision floating-point).       |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -3.4028235E+38 to 3.4028235E+38.          |    |
     * | | Range (unsigned) - From 0 to 3.4028235E+38 (simulated using     |    |
     * | | constraints).                                                   |    |
     * | |                                                                 |    |
     * | | Precision - Up to 6 decimal places.                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | -------------------------- MS SQL Server --------------------------    |
     * | | Storage size - 4 bytes (single-precision floating-point).       |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -3.4028235E+38 to 3.4028235E+38.          |    |
     * | | Range (unsigned) - From 0 to 3.4028235E+38 (simulated using     |    |
     * | | constraints).                                                   |    |
     * | |                                                                 |    |
     * | | Precision - Up to 7 decimal places.                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | ----------------------------- SQLite ------------------------------    |
     * | | Storage size - 8 bytes (double-precision floating-point).       |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -1.7976931348623157E+308 to               |    |
     * | | 1.7976931348623157E+308.                                        |    |
     * | | Range (unsigned) - From 0 to 1.7976931348623157E+308 (simulated |    |
     * | | using constraints).                                             |    |
     * | |                                                                 |    |
     * | | Precision - Up to 15 decimal places.                            |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
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
     * | Clause to define money data type column.                               |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | PostgreSQL, MS SQL Server                                              |
     * | ---------------------------------------------------------------------- |
     * | --------------------------- PostgreSQL ----------------------------    |
     * | | Storage size - 8 bytes (fixed-point).                           |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -2,147,483,648.00 to 2,147,483,647.99.    |    |
     * | | Range (unsigned) - From 0 to 2,147,483,647.99 (simulated using  |    |
     * | | constraints).                                                   |    |
     * | |                                                                 |    |
     * | | Precision - Up to 2 decimal places.                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | -------------------------- MS SQL Server --------------------------    |
     * | | Storage size - 8 bytes (fixed-point).                           |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -2,147,483,648.00 to 2,147,483,647.99.    |    |
     * | | Range (unsigned) - From 0 to 2,147,483,647.99 (simulated using  |    |
     * | | constraints).                                                   |    |
     * | |                                                                 |    |
     * | | Precision - Up to 4 decimal places.                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
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
     * | Clause to define small money data type column.                         |
     * | -------------- DBMS drivers that support this data type -------------- |
     * | MS SQL Server                                                          |
     * | ---------------------------------------------------------------------- |
     * | -------------------------- MS SQL Server --------------------------    |
     * | | Storage size - 4 bytes (fixed-point).                           |    |
     * | |                                                                 |    |
     * | | Range (signed) - From -214,748.3648 to 214,748.3647.            |    |
     * | | Range (unsigned) - From 0 to 214,748.3647 (simulated using      |    |
     * | | constraints).                                                   |    |
     * | |                                                                 |    |
     * | | Precision - Up to 4 decimal places.                             |    |
     * | |                                                                 |    |
     * | | Unsigned support - This driver do not support unsigned types    |    |
     * | | directly, so if you specify parameter "unsigned" as true that   |    |
     * | | statement will work by using CHECK(value >= 0).                 |    |
     * | -------------------------------------------------------------------    |
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
