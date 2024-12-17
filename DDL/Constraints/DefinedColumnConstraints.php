<?php

namespace Moirai\DDL\Constraints;

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
    private function bind(int $key, array $parameters = []): self
    {
        $this->blueprintInstance->columnsDefinitionsBindings[$this->column]['constraints'][$key] = $parameters;

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unsigned column constraint.                           |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     */
    public function unsigned(): self
    {
        return $this->bind(ColumnConstraints::UNSIGNED);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define check column constraint.                              |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     */
    public function check(): self
    {
        return $this->bind(ColumnConstraints::CHECK);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define autoincrement column constraint.                      |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, SQLite                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     */
    public function autoincrement(): self
    {
        return $this->bind(ColumnConstraints::AUTOINCREMENT);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define not null column constraint.                           |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     */
    public function notNull(): self
    {
        return $this->bind(ColumnConstraints::NOT_NULL);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define unique column constraint.                             |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     */
    public function unique(): self
    {
        return $this->bind(ColumnConstraints::UNIQUE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define default column constraint.                            |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param int|string|float $value
     * @return $this
     */
    public function default(int|string|float $value): self
    {
        return $this->bind(ColumnConstraints::DEFAULT, compact('value'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define collation column constraint.                          |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, SQLite                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param int|string|float $value
     * @return $this
     */
    public function collation(int|string|float $value): self
    {
        return $this->bind(ColumnConstraints::COLLATION, compact('value'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define collation column constraint.                          |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, SQLite                      |
     * | ---------------------------------------------------------------------- |
     * | Same as "collation".                                                   |
     * --------------------------------------------------------------------------
     * @param int|string|float $value
     * @return $this
     */
    public function collate(int|string|float $value): self
    {
        return $this->collation($value);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define charset column constraint.                            |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param int|string|float $value
     * @return $this
     */
    public function charset(int|string|float $value): self
    {
        return $this->bind(ColumnConstraints::CHARSET, compact('value'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define primary key column constraint.                        |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle, SQLite              |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     */
    public function primaryKey(): self
    {
        return $this->bind(ColumnConstraints::PRIMARY_KEY);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to define invisible column constraint.                          |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB                                                         |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     */
    public function invisible(): self
    {
        return $this->bind(ColumnConstraints::INVISIBLE);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to add comment for column.                                      |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, Oracle                                     |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $value
     * @return $this
     */
    public function comment(string $value): self
    {
        return $this->bind(ColumnConstraints::COMMENT, compact('value'));
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to modify column.                                               |
     * | ------------- DBMS drivers that support this constraint -------------- |
     * | MySQL, MariaDB, PostgreSQL, MS SQL Server, Oracle                      |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     */
    public function modify(): void
    {
        $this->blueprintInstance->modify[] = $this->column;
    }
}
