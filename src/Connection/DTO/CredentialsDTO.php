<?php

namespace Moirai\Connection\DTO;

use Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface;

class CredentialsDTO implements CredentialsDTOInterface
{
    /**
     * @var string
     */
    private string $host;

    /**
     * @var string|int
     */
    private string|int $port;

    /**
     * @var string
     */
    private string $database;

    /**
     * @var string
     */
    private string $username;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var string
     */
    private string $dbmsDriver;

    /**
     * CredentialsDTO constructor.
     *
     * @param string $host
     * @param string|int $port
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $dbmsDriver
     */
    private function __construct(
        string $host,
        string|int $port,
        string $database,
        string $username,
        string $password,
        string $dbmsDriver
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->dbmsDriver = $dbmsDriver;
    }

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
    ): self {
        return new self($host, $port, $database, $username, $password, $dbmsDriver);
    }

    /**
     * @return string
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * @return string|int
     */
    public function port(): string|int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function database(): string
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function password(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function dbmsDriver(): string
    {
        return $this->dbmsDriver;
    }
}
