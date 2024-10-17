<?php

namespace Moirai\DDL;

use Closure;
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
    public function getDriver()
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



    private function resolveAutoIncrementAndUnsignedParametersUsing(bool $autoIncrement, bool $unsigned): array
    {
        $parameters = [];

        if ($unsigned) {
            $parameters[] = 'UNSIGNED';
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
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function char(string $column, string|int|null $length = null): DefinedColumnAccessories
    {
        $length = $length ?? $this->defaultStringLength;

        return $this->bindColumn($column, $this->driver->getDataType('char'), compact('length'));
    }

    /**
     * @param string $column
     * @param string|int|null $length
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function string(string $column, string|int|null $length = null): DefinedColumnAccessories
    {
        $length = $length ?: $this->defaultStringLength;

        return $this->bindColumn($column, $this->driver->getDataType('string'), compact('length'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function tinyText(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('tinyText'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function text(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('text'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function mediumText(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('mediumText'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function longText(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('longText'));
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        return $this->bindColumn($column, $this->driver->getDataType('integer'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        return $this->bindColumn($column, $this->driver->getDataType('tinyInteger'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function smallInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        return $this->bindColumn($column, $this->driver->getDataType('smallInteger'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function mediumInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        return $this->bindColumn($column, $this->driver->getDataType('mediumInteger'), $parameters);
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
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        return $this->bindColumn($column, $this->driver->getDataType('bigInteger'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType('integer'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedTinyInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType('tinyInteger'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedSmallInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType('smallInteger'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedMediumInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType('mediumInteger'), $parameters);
    }

    /**
     * @param string $column
     * @param bool $autoIncrement
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): DefinedColumnAccessories
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        return $this->bindColumn($column, $this->driver->getDataType('bigInteger'), $parameters);
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
        return $this->floatBaseBinder('float', $column, $total, $places, $unsigned);
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
        return $this->floatBaseBinder('double', $column, $total, $places, $unsigned);
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
        return $this->floatBaseBinder('decimal', $column, $total, $places, $unsigned);
    }

    /**
     * @param string $column
     * @param int $total
     * @param int $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function unsignedFloat(string $column, int $total = 8, int $places = 2): DefinedColumnAccessories
    {
        return $this->floatBaseBinder('float', $column, $total, $places, true);
    }

    /**
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function unsignedDouble(string $column, int|null $total = null, int|null $places = null): DefinedColumnAccessories
    {
        return $this->floatBaseBinder('double', $column, $total, $places, true);
    }

    /**
     * @param string $column
     * @param int $total
     * @param int $places
     * @return \Moirai\DDL\DefinedColumnAccessories
     */
    public function unsignedDecimal(string $column, int $total = 8, int $places = 2): DefinedColumnAccessories
    {
        return $this->floatBaseBinder('decimal', $column, $total, $places, true);
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function boolean(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('boolean'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function bool(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('boolean'));
    }

    /**
     * @param string $column
     * @param array $whiteList
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function enum(string $column, array $whiteList): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('enum'),
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
        return $this->bindColumn($column, $this->driver->getDataType('set'),
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
        return $this->bindColumn($column, $this->driver->getDataType('json'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function jsonb(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('jsonb'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function date(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('date'));
    }

    /**
     * @param string $column
     * @param int $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function dateTime(string $column, int $precision = 0): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('dateTime'), ['(' . $precision . ')']);
    }

    /**
     * @param string $column
     * @param int $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function time(string $column, int $precision = 0): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('time'), ['(' . $precision . ')']);
    }

    /**
     * @param string $column
     * @param int $precision
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function timestamp(string $column, int $precision = 0): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('timestamp'), ['(' . $precision . ')']);
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function year(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('year'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function binary(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('binary'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function varbinary(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('varbinary'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function geometry(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('geometry'));
    }

    /**
     * @param string $column
     * @param int|string|null $srid
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function point(string $column, null|int|string $srid = null): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('point'), compact('srid'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function lineString(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('lineString'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function polygon(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('polygon'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multipoint(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('multipoint'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multiLineString(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('multiLineString'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function multiPolygon(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('multiPolygon'));
    }

    /**
     * @param string $column
     * @return \Moirai\DDL\DefinedColumnAccessories
     * @throws \Exception
     */
    public function geometryCollection(string $column): DefinedColumnAccessories
    {
        return $this->bindColumn($column, $this->driver->getDataType('geometryCollection'));
    }
}
