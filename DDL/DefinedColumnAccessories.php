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
     * @param Blueprint $blueprintInstance
     */
    public function __construct(string $column, Blueprint $blueprintInstance)
    {
        $this->column = $column;
        $this->blueprintInstance = $blueprintInstance;
    }

    /**
     * @param string $key
     */
    public function bindAccessory(string $key): void
    {
        $this->blueprintInstance->columns[$this->column][$key] = $value;
    }

    /**
     * @return $this
     */
    public function unsigned(): self
    {
        $this->bindAccessory(Accessories::UNSIGNED);

        return $this;
    }

    /**
     * @return $this
     */
    public function autoincrement(): self
    {
        $this->bindAccessory(Accessories::AUTOINCREMENT);

        return $this;
    }

    /**
     * @return $this
     */
    public function primary(): self
    {
        $this->bindAccessory(Accessories::PRIMARY);

        return $this;
    }

    /**
     * @return $this
     */
    public function nullable(): self
    {
        $this->deleteAccessory(Accessories::NULLABLE,);

        return $this;
    }

    /**
     * @return $this
     */
    public function unique(): self
    {
        $this->bindAccessory(Accessories::PRIMARY);

        return $this;
    }

    /**
     * @return $this
     */
    public function default(): self
    {
        $this->bindAccessory(Accessories::DEFAULT);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function collation(): self
    {
        $this->bindAccessory(Accessories::COLLATION);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function charset(): self
    {
        $this->bindAccessory(Accessories::CHARSET);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function comment(): self
    {
        $this->bindAccessory(Accessories::COMMENT);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function index(): self
    {
        $this->bindAccessory(Accessories::INDEX);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function invisible(): self
    {
        $this->bindAccessory(Accessories::INVISIBLE);

        return $this;
    }
}
