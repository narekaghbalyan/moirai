<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Connection\Configs\Configs;
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
     * @var \Moirai\Connection\Configs\Configs
     */
    private Configs $configs;

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
        $this->configs = new Configs('configs.php', $connectionKey);

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
        $dbmsDriver = $this->configs->getValue('db_driver') ?: AvailableDbmsDrivers::MYSQL;

        if ($dbmsDriver !== AvailableDbmsDrivers::SQLITE) {
            $this->dto = CredentialsDTO::create(
                $this->configs->getValue('db_host', true),
                $this->configs->getValue('db_port') ?? 3306,
                $this->configs->getValue('db_database', true),
                $this->configs->getValue('db_username') ?? '',
                $this->configs->getValue('db_password') ?? '',
                $dbmsDriver
            );

            return;
        }

        $this->dto = FileConnectionDTO::create(
            $this->configs->getValue('db_file_path', true),
            $dbmsDriver
        );
    }
}
