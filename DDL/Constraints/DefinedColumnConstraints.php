<?php

namespace Moirai\DDL\Constraints;

use Moirai\DDL\AlterColumnActions;
use Moirai\DDL\Blueprint;

class DefinedColumnConstraints
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
    public function bind(int $key, array $parameters = []): self
    {
        $this->blueprintInstance->columns[$this->column]['constraints'][$key] = $parameters;

        return $this;
    }

    /**
     * @return $this
     */
    public function unsigned(): self
    {
        return $this->bind(ColumnConstraints::UNSIGNED);
    }

    /**
     * @return $this
     */
    public function check(): self
    {
        return $this->bind(ColumnConstraints::CHECK);
    }

    /**
     * @return $this
     */
    public function autoincrement(): self
    {
        return $this->bind(ColumnConstraints::AUTOINCREMENT);
    }

    /**
     * @return $this
     */
    public function notNull(): self
    {
        return $this->bind(ColumnConstraints::NOT_NULL);
    }

    /**
     * @return $this
     */
    public function unique(): self
    {
        return $this->bind(ColumnConstraints::UNIQUE);
    }

    /**
     * @param int|string|float $value
     * @return $this
     */
    public function default(int|string|float $value): self
    {
        return $this->bind(ColumnConstraints::DEFAULT, compact('value'));
    }

    /**
     * @param int|string|float $value
     * @return $this
     */
    public function collation(int|string|float $value): self
    {
        return $this->bind(ColumnConstraints::COLLATION, compact('value'));
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
        return $this->bind(ColumnConstraints::CHARSET, compact('value'));
    }

    /**
     * @return $this
     */
    public function primaryKey(): self
    {
        return $this->bind(ColumnConstraints::PRIMARY_KEY);
    }

    /**
     * @return $this
     */
    public function invisible(): self
    {
        return $this->bind(ColumnConstraints::INVISIBLE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function comment(string $value): self
    {
        return $this->bind(ColumnConstraints::COMMENT, compact('value'));
    }

    public function modify(): void
    {
        $this->blueprintInstance->modify[] = $this->column;
    }
}
