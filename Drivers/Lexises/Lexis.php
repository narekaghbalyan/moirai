<?php

namespace Moirai\Drivers\Lexises;

use Exception;

abstract class Lexis
{
    /**
     * @var array
     */
    protected array $dataTypes;

    /**
     * @var array
     */
    protected array $columnConstraints;

    /**
     * @var array
     */
    protected array $tableConstraints;

    /**
     * @return array
     */
    public function getDataTypes(): array
    {
        return $this->dataTypes;
    }

    /**
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public function getDataType(string $key): string
    {
        if (!isset($this->dataTypes[$key])) {
            throw new Exception('This data type is not supported by this driver.');
        }

        return $this->dataTypes[$key];
    }

    /**
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public function getColumnConstraint(string $key): string
    {
        if (!isset($this->columnConstraints[$key])) {
            throw new Exception('This column constraint is not supported by this driver.');
        }

        return $this->columnConstraints[$key];
    }

    /**
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public function getTableConstraint(string $key): string
    {
        if (!isset($this->tableConstraints[$key])) {
            throw new Exception('This table constraint is not supported by this driver.');
        }

        return $this->tableConstraints[$key];
    }
}