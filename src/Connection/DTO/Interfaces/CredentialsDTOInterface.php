<?php

namespace Moirai\Connection\DTO\Interfaces;

interface CredentialsDTOInterface
{
    /**
     * @param string $host
     * @param string|int $port
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $dbmsDriver
     * @return static
     */
    public static function create(
        string $host,
        string|int $port,
        string $database,
        string $username,
        string $password,
        string $dbmsDriver
    ): self;

    /**
     * @return string
     */
    public function host(): string;

    /**
     * @return string|int
     */
    public function port(): string|int;

    /**
     * @return string
     */
    public function database(): string;

    /**
     * @return string
     */
    public function username(): string;

    /**
     * @return string
     */
    public function password(): string;

    /**
     * @return string
     */
    public function dbmsDriver(): string;
}
