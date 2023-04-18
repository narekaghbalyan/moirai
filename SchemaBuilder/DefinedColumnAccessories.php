<?php

namespace Moarai\SchemaBuilder;

use Exception;

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
        if (!is_null($accessoryKey)) {
            $this->blueprintInstance->columns[$this->column][$accessoryKey] = $accessory;
        } else {
            $this->blueprintInstance->columns[$this->column][] = $accessory;
        }

    }

    public function checkAccessoryExistence(string $accessoryKey): bool
    {
        return !empty($this->blueprintInstance->columns[$this->column][$accessoryKey]);
    }

    public function getAccessory(string $accessoryKey): string|array
    {
        return $this->blueprintInstance->columns[$this->column][$accessoryKey];
    }

    /**
     * @return $this
     */
    public function nullable(): self
    {
        $this->bindAccessory('NULL', 'value');

        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function default(mixed $value): self
    {
        $this->bindAccessory('DEFAULT ' . $value);

        return $this;
    }

    // TODO
    public function unique(): self
    {
        $uniqueConstraints = $this->getAccessory('unique');

        $accessory = 'CONSTRAINT unique_constraints UNIQUE (' .

            $this->bindAccessory('UNIQUE', 'unique');

        return $this;
    }

    /**
     * @param string $collation
     * @return $this
     */
    public function collation(string $collation): self
    {
        $this->bindAccessory('COLLATE ' . $collation);

        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function charset(string $charset): self
    {
        $this->bindAccessory('CHARACTER SET ' . $charset);

        return $this;
    }

    /**
     * @return $this
     */
    public function first(): self
    {
        $this->bindAccessory('FIRST ');

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function after(string $column): self
    {
        $this->bindAccessory('AFTER ' . $column);

        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment): self
    {
        $this->bindAccessory('COMMENT ' . $comment);

        return $this;
    }

    /**
     * @return $this
     */
    public function autoIncrement(): self
    {
        $this->bindAccessory('AUTO_INCREMENT');

        return $this;
    }

    /**
     * TODO
     * @return $this
     */
    public function index(string $indexName): self
    {
        $this->bindAccessory('INDEX ' . $indexName);

        return $this;
    }

    
}