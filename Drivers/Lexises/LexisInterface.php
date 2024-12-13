<?php

namespace Moirai\Drivers\Lexises;

interface LexisInterface
{
    /**
     * @return array
     */
    public function getDataTypes(): array;

    /**
     * @param string $key
     * @return string
     */
    public function getDataType(string $key): string;

    /**
     * @param string $key
     * @return string
     */
    public function getColumnConstraint(string $key): string;

    /**
     * @param string $key
     * @return string
     */
    public function getTableConstraint(string $key): string;

    /**
     * @param string $key
     * @return string
     */
    public function getAlterAction(string $key): string;
}