<?php

namespace Moarai\SchemaBuilder;

class DefinedColumnAccessories
{
    protected string $column;

    protected Blueprint $blueprintInstance;

    public function __construct(string $column, Blueprint $blueprintInstance)
    {
        $this->column = $column;

        $this->blueprintInstance = $blueprintInstance;
    }

    public function bindAccessory(string $accessory, string $accessoryKey = null): void
    {
        $this->blueprintInstance->columns[$this->column][
            !is_null($accessory) ? $accessoryKey : ''
        ] = $accessory;
    }

    public function nullable()
    {
        $this->bindAccessory('NULL', 'value');

        return $this;
    }

    public function default(mixed $value)
    {
        $this->bindAccessory('DEFAULT' . ' ' . $value);

        return $this;
    }

    public function unique()
    {
        $this->bindAccessory('UNIQUE');

        return $this;
    }
}