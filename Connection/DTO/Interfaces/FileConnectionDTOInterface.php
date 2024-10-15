<?php

namespace Moirai\Connection\DTO\Interfaces;

interface FileConnectionDTOInterface
{
    /**
     * @param string $filePath
     * @param string $dbmsDriver
     * @return static
     */
    public static function create(string $filePath, string $dbmsDriver): self;

    /**
     * @return string
     */
    public function filePath(): string;

    /**
     * @return string
     */
    public function dbmsDriver(): string;
}
