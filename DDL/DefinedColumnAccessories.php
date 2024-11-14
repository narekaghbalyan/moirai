<?php

namespace Moirai\DDL;

use Exception;
use Moirai\Drivers\AvailableDbmsDrivers;

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
     * @param string $value
     * @param bool $belongsTable
     * @param bool $append
     */
    public function bindAccessory(string $key, string $value, bool $belongsTable = false, bool $append = false): void
    {
        if (!$belongsTable) {
            if (!$append) {
                $this->blueprintInstance->columns[$this->column][$key] = $value;
            } else {
                $this->blueprintInstance->columns[$this->column][$key][] = $value;
            }
        } else {
            if (!$append) {
                $this->blueprintInstance->tableAccessories[$key] = $value;
            } else {
                $this->blueprintInstance->tableAccessories[$key][] = $value;
            }
        }
    }

    /**
     * @param string $accessoryKey
     * @param bool $isTableAccessory
     */
    public function deleteAccessory(string $accessoryKey, bool $isTableAccessory = false): void
    {
        if (!$isTableAccessory) {
            unset($this->blueprintInstance->columns[$this->column][$accessoryKey]);
        } else {
            unset($this->blueprintInstance->tableAccessories[$accessoryKey]);
        }
    }

    /**
     * @param string $accessoryKey
     * @param bool $isTableAccessory
     * @return bool
     */
    public function checkAccessoryExistence(string $accessoryKey, bool $isTableAccessory = false): bool
    {
        if (!$isTableAccessory) {
            return !empty($this->blueprintInstance->columns[$this->column][$accessoryKey]);
        }

        return !empty($this->blueprintInstance->tableAccessories[$accessoryKey]);
    }

    /**
     * @return $this
     */
    public function nullable(): self
    {
        $this->deleteAccessory('nullable');

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

    /**
     * @return $this
     */
    public function unique(): self
    {
        if (!empty($this->blueprintInstance->tableAccessories['unique']['columns'])) {
            $this->blueprintInstance->tableAccessories['unique']['prefix'] = 'CONSTRAINT unique_constraints UNIQUE';
        }

        $this->blueprintInstance->tableAccessories['unique']['columns'][] = $this->column;

        return $this;
    }

    /**
     * @param string $collation
     * @return $this
     * @throws Exception
     */
    public function collation(string $collation): self
    {
        $driver = $this->blueprintInstance->getDriver();

        if (in_array($driver, [AvailableDbmsDrivers::SQLITE, AvailableDbmsDrivers::ORACLE])) {
            throw new Exception('DriverInterface ' . $driver . ' does not support this function.');
        }

        $this->bindAccessory('COLLATE ' . $collation);

        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     * @throws Exception
     */
    public function charset(string $charset): self
    {
        $driver = $this->blueprintInstance->getDriver();

        if (!in_array($driver, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])) {
            throw new Exception('DriverInterface ' . $driver . ' does not support this function.');
        }

        $prefix = 'CHARACTER SET ';

        if ($driver === AvailableDbmsDrivers::MARIADB) {
            $prefix .= '= ';
        }

        $this->bindAccessory($prefix . $charset);

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function first(): self
    {
        $driver = $this->blueprintInstance->getDriver();

        if (!in_array($driver, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])) {
            throw new Exception('DriverInterface ' . $driver . ' does not support this function.');
        }

        $this->bindAccessory('FIRST ');

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function after(string $column): self
    {
        $driver = $this->blueprintInstance->getDriver();

        if (!in_array($driver, [AvailableDbmsDrivers::MYSQL, AvailableDbmsDrivers::MARIADB])) {
            throw new Exception('DriverInterface ' . $driver . ' does not support this function.');
        }

        $this->bindAccessory('AFTER ' . $column);

        return $this;
    }








    /**
     * @param string $comment
     * @return $this
     * @throws Exception
     */
    public function comment(string $comment): self
    {
        $driver = $this->blueprintInstance->getDriver();

        if ($driver === AvailableDbmsDrivers::MYSQL || $driver === AvailableDbmsDrivers::MARIADB) {
            $this->bindAccessory('COMMENT ' . $comment);
        } elseif ($driver === AvailableDbmsDrivers::POSTGRESQL) {
            $this->blueprintInstance->afterTableDefinition[] = 'COMMENT ON COLUMN '
                . $this->blueprintInstance->table
                . '.'
                . $this->column
                . ' is '
                . $comment;
        } else {
            throw new Exception('DriverInterface ' . $driver . ' does not support this function.');
        }

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
     * @return $this
     */
    public function unsigned(): self
    {
        $this->bindAccessory('UNSIGNED');

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

    /**
     * @return $this
     */
    public function invisible(): self
    {
        $this->bindAccessory('INVISIBLE');

        return $this;
    }

    /**
     * @return $this
     */
    public function primary(): self
    {
        $this->bindAccessory('PRIMARY KEY');

        return $this;
    }
}
