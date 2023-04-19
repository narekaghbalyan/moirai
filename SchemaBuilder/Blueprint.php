<?php

namespace Moarai\SchemaBuilder;

use Closure;
use Moarai\Drivers\MySqlDriver;

class Blueprint
{
    protected $driver;

    protected string $table;

    public array $columns = [];

    public array $tableAccessories = [];

    private int $defaultStringLength = 255;

    public function __construct(string $table, Closure|null $callback = null)
    {
        $this->driver = new MySqlDriver();

        $this->table = $table;

        if (!is_null($callback)) {
            $callback($this);
        }

        $this->sewDefinedColumns();
    }

    private function sewDefinedColumns(): string
    {
        if (empty($this->columns)) {
            return '';
        }

        $sewedColumns = [];

        foreach ($this->columns as $column => $parameters) {
            $sewedColumns[] = $column . ' ' . implode(' ', $parameters);
        }

        dd(implode(', ', $sewedColumns));
    }

    private function bindColumn(string $column, string $dataType, array $parameters = []): DefinedColumnAccessories
    {
        $this->columns[$column] = array_merge(compact('dataType'), $parameters);

        $this->columns[$column]['value'] = 'NOT NULL';

        return new DefinedColumnAccessories($column, $this);
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
                                    bool $unsigned = false): void
    {
        $parameters = [];

        if (!is_null($total) && !is_null($places)) {
            $parameters[] = '(' . $total . ', ' . $places . ')';
        }

        $parameters = array_merge(
            $parameters,
            $this->resolveAutoIncrementAndUnsignedParametersUsing(false, $unsigned)
        );

        $this->bindColumn($column, $this->driver->getDataType($dataType), $parameters);
    }


    public function char(string $column, string|int|null $length = null)
    {
        $length = $length ?: $this->defaultStringLength;

        return $this->bindColumn($column, $this->driver->getDataType('char'), compact('length'));
    }


    public function string(string $column, string|int|null $length = null)
    {
        $length = $length ?: $this->defaultStringLength;

        $this->bindColumn($column, $this->driver->getDataType('string'), compact('length'));
    }

    public function tinyText(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('tinyText'));
    }

    public function text(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('text'));
    }

    public function mediumText(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('mediumText'));
    }

    public function longText(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('longText'));
    }

    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        $this->bindColumn($column, $this->driver->getDataType('integer'), $parameters);

        return $this;
    }

    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        $this->bindColumn($column, $this->driver->getDataType('tinyInteger'), $parameters);
    }

    public function smallInteger(string $column, bool $autoIncrement = false, bool $unsigned = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        $this->bindColumn($column, $this->driver->getDataType('smallInteger'), $parameters);
    }

    public function mediumInteger(string $column, bool $autoIncrement = false, bool $unsigned = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        $this->bindColumn($column, $this->driver->getDataType('mediumInteger'), $parameters);
    }

    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        $this->bindColumn($column, $this->driver->getDataType('bigInteger'), $parameters);
    }

    public function unsignedInteger(string $column, bool $autoIncrement = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, $unsigned);

        $this->bindColumn($column, $this->driver->getDataType('integer'), $parameters);
    }

    public function unsignedTinyInteger(string $column, bool $autoIncrement = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        $this->bindColumn($column, $this->driver->getDataType('tinyInteger'), $parameters);
    }

    public function unsignedSmallInteger(string $column, bool $autoIncrement = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        $this->bindColumn($column, $this->driver->getDataType('smallInteger'), $parameters);
    }

    public function unsignedMediumInteger(string $column, bool $autoIncrement = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        $this->bindColumn($column, $this->driver->getDataType('mediumInteger'), $parameters);
    }

    public function unsignedBigInteger(string $column, bool $autoIncrement = false)
    {
        $parameters = $this->resolveAutoIncrementAndUnsignedParametersUsing($autoIncrement, true);

        $this->bindColumn($column, $this->driver->getDataType('bigInteger'), $parameters);
    }

    public function float(string $column, int $total = 8, int $places = 2, bool $unsigned = false)
    {
        $this->floatBaseBinder('float', $column, $total, $places, $unsigned);
    }

    public function double(string $column, int|null $total = null, int|null $places = null, bool $unsigned = false)
    {
        $this->floatBaseBinder('double', $column, $total, $places, $unsigned);
    }

    public function decimal(string $column, int $total = 8, int $places = 2, bool $unsigned = false)
    {
        $this->floatBaseBinder('decimal', $column, $total, $places, $unsigned);
    }

    public function unsignedFloat(string $column, int $total = 8, int $places = 2)
    {
        $this->floatBaseBinder('float', $column, $total, $places, true);
    }

    public function unsignedDouble(string $column, int|null $total = null, int|null $places = null)
    {
        $this->floatBaseBinder('double', $column, $total, $places, true);
    }

    public function unsignedDecimal(string $column, int $total = 8, int $places = 2)
    {
        $this->floatBaseBinder('decimal', $column, $total, $places, true);
    }

    public function boolean(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('boolean'));
    }

    public function bool(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('boolean'));
    }

    public function enum(string $column, array $whiteList)
    {
        $this->bindColumn($column, $this->driver->getDataType('enum'),
            [implode(', ', $whiteList)]
        );
    }

    public function set(string $column, array $whiteList)
    {
        $this->bindColumn($column, $this->driver->getDataType('set'),
            [implode(', ', $whiteList)]
        );
    }

    public function json(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('json'));
    }

    public function jsonb(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('jsonb'));
    }

    public function date(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('date'));
    }

    public function dateTime(string $column, int $precision = 0)
    {
        $this->bindColumn($column, $this->driver->getDataType('dateTime'), ['(' . $precision . ')']);
    }

    public function time(string $column, int $precision = 0)
    {
        $this->bindColumn($column, $this->driver->getDataType('time'), ['(' . $precision . ')']);
    }

    public function timestamp(string $column, int $precision = 0)
    {
        $this->bindColumn($column, $this->driver->getDataType('timestamp'), ['(' . $precision . ')']);
    }

    public function year(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('year'));
    }

    public function binary(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('binary'));
    }

    public function varbinary(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('varbinary'));
    }

    public function geometry(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('geometry'));
    }

    public function point(string $column, null|int|string $srid = null)
    {
        $this->bindColumn($column, $this->driver->getDataType('point'), compact('srid'));
    }

    public function lineString(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('lineString'));
    }

    public function polygon(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('polygon'));
    }

    public function multipoint(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('multipoint'));
    }

    public function multiLineString(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('multiLineString'));
    }

    public function multiPolygon(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('multiPolygon'));
    }

    public function geometryCollection(string $column)
    {
        $this->bindColumn($column, $this->driver->getDataType('geometryCollection'));
    }
}