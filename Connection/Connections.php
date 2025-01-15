<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Connection\DTO\CredentialsDTO;
use Moirai\Connection\DTO\FileConnectionDTO;
use Moirai\Connection\DTO\OptionsDTO;
use Moirai\Drivers\AvailableDbmsDrivers;
use Moirai\Drivers\DriverInterface;

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
    private string $configBasePath;

    /**
     * @var string
     */
    private string $dbmsDriver;

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

        if (empty($this->configs->getValue('connections.'  . $connectionKey))) {
            throw new Exception(
                'Could not find a connection credentials with connection key "'
                . $connectionKey
                . '" in config file.'
            );
        }

        $this->configBasePath = 'connections.' . $connectionKey . '.';

        $this->dbmsDriver = $this->configs->getValue($this->sculptConfigPath('db_driver'))
            ?: AvailableDbmsDrivers::MYSQL;

        $this->dbh = new DBH(
            $this->initializeDTO(),
            OptionsDTO::create(
                $this->configs->getValue($this->sculptConfigPath('persistent')),
                $this->configs->getValue($this->sculptConfigPath('emulate_prepares')),
                $this->configs->getValue($this->sculptConfigPath('autocommit')),
                $this->configs->getValue($this->sculptConfigPath('case')),
                $this->configs->getValue($this->sculptConfigPath('error_mode')),
                $this->configs->getValue($this->sculptConfigPath('default_fetch_mode')),
                $this->configs->getValue($this->sculptConfigPath('timeout')),
                $this->configs->getValue($this->sculptConfigPath('cursor')),
                $this->configs->getValue($this->sculptConfigPath('statement_class'))
            )
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
     * @return \Moirai\Connection\DBH
     */
    public function getDbh(): DBH
    {
        return $this->dbh;
    }

    /**
     * @return string
     */
    public function getDbmsDriver(): string
    {
        return $this->dbmsDriver;
    }

    /**
     * @return \Moirai\Drivers\DriverInterface
     */
    public function getDbmsDriverInstance(): DriverInterface
    {
        return new ($this->dbmsDriver);
    }

    /**
     * @return \Moirai\Connection\DTO\CredentialsDTO|\Moirai\Connection\DTO\FileConnectionDTO
     * @throws \Exception
     */
    private function initializeDTO(): CredentialsDTO|FileConnectionDTO
    {
        if ($this->dbmsDriver !== AvailableDbmsDrivers::SQLITE) {
            return CredentialsDTO::create(
                $this->configs->getValue($this->sculptConfigPath('db_host'), true),
                $this->configs->getValue($this->sculptConfigPath('db_port')) ?? 3306,
                $this->configs->getValue($this->sculptConfigPath('db_database'), true),
                $this->configs->getValue($this->sculptConfigPath('db_username')) ?? '',
                $this->configs->getValue($this->sculptConfigPath('db_password')) ?? '',
                $this->dbmsDriver
            );
        }

        return FileConnectionDTO::create(
            $this->configs->getValue($this->sculptConfigPath('db_file_path'), true),
            $this->dbmsDriver
        );
    }

    /**
     * @param string $key
     * @return string
     */
    private function sculptConfigPath(string $key): string
    {
        return $this->configBasePath . $key;
    }
}
