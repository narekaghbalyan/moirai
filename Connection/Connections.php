<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Connection\DTO\CredentialsDTO;
use Moirai\Connection\DTO\FileConnectionDTO;
use Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface;
use Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface;
use Moirai\Connection\DTO\OptionsDTO;
use Moirai\Drivers\AvailableDbmsDrivers;

class Connections
{
    /**
     * @var array
     */
    private static array $instances = [];

    /**
     * @var \Moirai\Connection\Configs
     */
    private Configs $configs;

    /**
     * @var string
     */
    private string $connectionKey;

    /**
     * @var \Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface|\Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface
     */
    private CredentialsDTOInterface|FileConnectionDTOInterface $dto;

    /**
     * DBH - Database Handle
     *
     * @var \Moirai\Connection\DBH
     */
    private DBH $dbh;

    /**
     * Connection constructor.
     *
     * @param string $connectionKey
     * @throws Exception
     */
    private function __construct(string $connectionKey)
    {
        $this->configs = new Configs('configs.php');

        if (empty($this->configs->getValue('connections'))) {
            throw new Exception('Connection(s) are empty in configs file.');
        }

        if (empty($this->configs->getValue('connections.default'))) {
            throw new Exception(
                'Could not find a connection credentials with connection key "'
                . $connectionKey
                . '" in config file.'
            );
        }

        $this->connectionKey = $connectionKey;

        $this->initializeDTO();

        $this->dbh = new DBH(
            $this->dto,
            OptionsDTO::create($this->configs->getValue('persistent'))
        );
    }

    private function __clone()
    {
    }

    /**
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a connections.');
    }

    /**
     * @param string $connectionKey
     * @return static
     * @throws \Exception
     */
    public static function getInstance(string $connectionKey): self
    {
        return static::$instances[$connectionKey]
            ?? (static::$instances[$connectionKey] = new static($connectionKey));
    }

    /**
     * @throws \Exception
     */
    private function initializeDTO()
    {
        $dbmsDriver = $this->configs->getValue($this->sculptConfigPath('db_driver')) ?: AvailableDbmsDrivers::MYSQL;

        if ($dbmsDriver !== AvailableDbmsDrivers::SQLITE) {
            $this->dto = CredentialsDTO::create(
                $this->configs->getValue($this->sculptConfigPath('db_host'), true),
                $this->configs->getValue($this->sculptConfigPath('db_port')) ?? 3306,
                $this->configs->getValue($this->sculptConfigPath('db_database'), true),
                $this->configs->getValue($this->sculptConfigPath('db_username')) ?? '',
                $this->configs->getValue($this->sculptConfigPath('db_password')) ?? '',
                $dbmsDriver
            );

            return;
        }

        $this->dto = FileConnectionDTO::create(
            $this->configs->getValue($this->sculptConfigPath('db_file_path'), true),
            $dbmsDriver
        );
    }

    /**
     * @param string $key
     * @return string
     */
    private function sculptConfigPath(string $key): string
    {
        return 'connections.' . $this->connectionKey . '.' . $key;
    }
}
