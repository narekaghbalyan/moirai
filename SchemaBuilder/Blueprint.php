<?php

namespace Moarai\SchemaBuilder;

use Closure;
use Moarai\Drivers\MySqlDriver;

class Blueprint
{
    protected $driver;

    protected string $table;

    protected array $columns = [];

    private int $defaultStringLength = 255;

    public function __construct(string $table, Closure|null $callback = null)
    {
        $this->driver = new MySqlDriver();

        $this->table = $table;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    private function bindColumn(string $column, string $dataType, array $parameters)
    {
        $this->columns[$column] = array_merge(compact('dataType'), $parameters);
    }

    public function char(string $column, string|int|null $length = null)
    {
        $length = $length ?: $this->defaultStringLength;

        $this->bindColumn($column, $this->driver->getDataType('char'), compact('length'));
    }

    public function string(string $column, string|int|null $length = null)
    {
        $length = $length ?: $this->defaultStringLength;

        $this->bindColumn($column, $this->driver->getDataType('string'), compact('length'));
    }
}