<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Drivers\AvailableDbmsDrivers;
use PDO;

class Connection
{
    /**
     * @var \Moirai\Connection\Configs
     */
    private Configs $configs;

    /**
     * @var string
     */
    private string $connectionKey;

    /**
     * @var array|string[]
     */
    private array $localAndConfigsCredentialsKeysConformity = [
        'host' => 'db_host',
        'port' => 'db_port',
        'database' => 'db_database',
        'username' => 'db_username',
        'password' => 'db_password',
        'dbms_driver' => 'db_driver',
        'file_path' => 'db_file_path'
    ];

    /**
     * @var array
     */
    private array $localAvailableDbmsDrivers;

    /**
     * @var array
     */
    private array $pdoAvailableDbmsDrivers;

    /**
     * @var array|string[]
     */
    private array $localAndPdoDbmsDriversConformity = [
        AvailableDbmsDrivers::MYSQL => 'mysql',
        AvailableDbmsDrivers::POSTGRESQL => 'pgsql',
        AvailableDbmsDrivers::SQLITE => 'sqlite',
        AvailableDbmsDrivers::MS_SQL_SERVER => 'sqlsrv',
        AvailableDbmsDrivers::MARIADB => 'mysql',
        AvailableDbmsDrivers::ORACLE => 'oci',
    ];



    /**
     * DBH - Database Handle
     *
     * @var mixed
     */
    private mixed $dbh;

    /**
     * Connection constructor.
     *
     * @param string $connectionKey
     * @throws Exception
     */
    public function __construct(string $connectionKey)
    {
        $this->localAvailableDbmsDrivers = AvailableDbmsDrivers::getDrivers();
        $this->pdoAvailableDbmsDrivers = PDO::getAvailableDrivers();







        $this->configs = new Configs('configs.php', $connectionKey);
        $this->dbh->initializeConnection();



        $this->dbh = new DBH();


        $this->applyCredentials();


    }




}
