<?php

namespace Moirai\DDL;

class DefinedColumnAccessories
{
    /**
     * @var string
     */
    private string $column;

    /**
     * @var Blueprint
     */
    private Blueprint $blueprintInstance;

    /**
     * DefinedColumnAccessories constructor.
     *
     * @param string $column
     * @param \Moirai\DDL\Blueprint $blueprintInstance
     */
    public function __construct(string $column, Blueprint $blueprintInstance)
    {
        $this->column = $column;
        $this->blueprintInstance = $blueprintInstance;
    }

    /**
     * @param int $key
     * @param array $parameters
     * @return $this
     */
    public function bindAccessory(int $key, array $parameters = []): self
    {
        $this->blueprintInstance->columns[$this->column]['accessories'][$key] = $parameters;

        return $this;
    }

    /**
     * @return $this
     */
    public function unsigned(): self
    {
        return $this->bindAccessory(Accessories::UNSIGNED);
    }

    /**
     * @return $this
     */
    public function autoincrement(): self
    {
        return $this->bindAccessory(Accessories::AUTOINCREMENT);
    }

    /**
     * @return $this
     */
    public function primary(): self
    {
        return $this->bindAccessory(Accessories::PRIMARY);
    }

    /**
     * @return $this
     */
    public function notNull(): self
    {
        return $this->bindAccessory(Accessories::NOT_NULL);
    }

    /**
     * @return $this
     */
    public function unique(): self
    {
        return $this->bindAccessory(Accessories::PRIMARY);
    }

    /**
     * @param int|string|float $value
     * @return $this
     */
    public function default(int|string|float $value): self
    {
        return $this->bindAccessory(Accessories::DEFAULT, compact('value'));
    }

    /**
     * @param int|string|float $value
     * @return $this
     */
    public function collation(int|string|float $value): self
    {
        return $this->bindAccessory(Accessories::COLLATION, compact('value'));
    }

    /**
     * @param int|string|float $value
     * @return $this
     */
    public function collate(int|string|float $value): self
    {
        return $this->collation($value);
    }

    /**
     * @param int|string|float $value
     * @return $this
     */
    public function charset(int|string|float $value): self
    {
        return $this->bindAccessory(Accessories::CHARSET, compact('value'));
    }

    /**
     * @param string $value
     * @return $this
     */
    public function comment(string $value): self
    {
        return $this->bindAccessory(Accessories::COMMENT, compact('value'));
    }

    /**
     * @param string $name
     * @param string $column
     * @return $this
     */
    public function index(string $name, string $column): self
    {
        return $this->bindAccessory(Accessories::INDEX, compact('name', 'column'));
    }

    /**
     * @return $this
     */
    public function invisible(): self
    {
        return $this->bindAccessory(Accessories::INVISIBLE);
    }
}
