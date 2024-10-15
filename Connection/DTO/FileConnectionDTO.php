<?php

namespace Moirai\Connection\DTO;

use Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface;

class FileConnectionDTO implements FileConnectionDTOInterface
{
    /**
     * @var string
     */
    private string $filePath;

    /**
     * @var string
     */
    private string $dbmsDriver;

    /**
     * FileConnectionDTO constructor.
     *
     * @param string $filePath
     * @param string $dbmsDriver
     */
    private function __construct(string $filePath, string $dbmsDriver)
    {
        $this->filePath = $filePath;
        $this->dbmsDriver = $dbmsDriver;
    }

    /**
     * @param string $filePath
     * @param string $dbmsDriver
     * @return static
     */
    public static function create(string $filePath, string $dbmsDriver): self
    {
        return new self($filePath, $dbmsDriver);
    }

    /**
     * @return string
     */
    public function filePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function dbmsDriver(): string
    {
        return $this->dbmsDriver;
    }
}
