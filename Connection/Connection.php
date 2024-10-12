<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Drivers\AvailableDbmsDrivers;
use PDO;
use PDOException;

class Connection
{
    /**
     * @var array
     */
    private array $configs;

    /**
     * @var string
     */
    private string $connectionKey;

    /**
     * @var array
     */
    private array $credentials;

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
     * @var array|bool[]
     */
    private array $options = [
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_PERSISTENT => true
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
        $this->configs = include('configs.php');
        $this->connectionKey = $connectionKey;
        $this->localAvailableDbmsDrivers = AvailableDbmsDrivers::getDrivers();
        $this->pdoAvailableDbmsDrivers = PDO::getAvailableDrivers();

        $this->validateConfigs();

        $this->applyCredentials();

        $this->initialize();
    }

    /**
     * @throws Exception
     */
    private function validateConfigs()
    {
        if (empty($this->configs['connections'])) {
            throw new Exception('Connection(s) are empty in config file.');
        }

        if (empty($this->configs['connections'][$this->connectionKey])) {
            throw new Exception(
                'Could not find a connection credentials with connection key "'
                . $this->connectionKey
                . '" in config file.'
            );
        }

        if (empty($this->configs['connections'][$this->connectionKey]['db_driver'])) {
            throw new Exception(
                'Database management system driver is required. It not specified or value is empty. It must be 
                specified in the configs file under the key "db_driver" in the connection "'
                . $this->connectionKey
                . '".'
            );
        }

        if (!in_array(
            $this->configs['connections'][$this->connectionKey]['db_driver'],
            $this->localAvailableDbmsDrivers
        )) {
            throw new Exception(
                'Database management system driver "'
                . $this->configs['connections'][$this->connectionKey]['db_driver']
                . '" is not supported. Only the following drivers are supported: "'
                . implode('", "', $this->localAvailableDbmsDrivers)
                . '".'
            );
        }

        if (!in_array(
            $this->localAndPdoDbmsDriversConformity[$this->configs['connections'][$this->connectionKey]['db_driver']],
            $this->pdoAvailableDbmsDrivers
        )) {
            throw new Exception(
                'Database management system driver "'
                . $this->configs['connections'][$this->connectionKey]['db_driver']
                . '" is not supported by PDO. Only the following drivers are supported: "'
                . implode('", "', $this->pdoAvailableDbmsDrivers)
                . '". The driver module may not be installed.'
            );
        }
    }


    /**
     * @throws Exception
     */
    private function applyCredentials()
    {
        if ($this->configs['connections'][$this->connectionKey]['db_driver'] !== AvailableDbmsDrivers::SQLITE) {
            $this->credentials = [
                'host' => ['config_key' => 'db_host', 'required' => true, 'value' => null],
                'port' => ['config_key' => 'db_port', 'required' => true, 'value' => null],
                'database' => ['config_key' => 'db_database', 'required' => true, 'value' => null],
                'username' => ['config_key' => 'db_username', 'required' => false, 'value' => ''],
                'password' => ['config_key' => 'db_password', 'required' => false, 'value' => ''],
                'dbms_driver' => ['config_key' => 'db_driver', 'required' => true, 'value' => null]
            ];
        } else {
            $this->credentials = [
                'file_path' => ['config_key' => 'db_file_path', 'required' => true, 'value' => null],
                'dbms_driver' => ['config_key' => 'db_driver', 'required' => true, 'value' => null]
            ];
        }

        foreach ($this->credentials as $key => $credential) {
            if (!isset($this->configs['connections'][$this->connectionKey][$credential['config_key']])) {
                throw new Exception(
                    'The connection '
                    . $key
                    . ' could not be found. It must be specified in the configs file under the key "'
                    . $credential['config_key']
                    . '" in the connection "'
                    . $this->connectionKey
                    . '". If it should not have a value then you can specify it with an empty value but you must be 
                    sure to declare it in the above location.'
                );
            }

            $this->credentials[$key]['value'] = $this->configs['connections'][$this->connectionKey][$credential['config_key']];

            if (!$credential['required']) {
                continue;
            }

            if (empty($this->configs['connections'][$this->connectionKey][$credential['config_key']])) {
                throw new Exception(
                    'The connection '
                    . $key
                    . ' is required, but specified value is empty. It must be specified in the configs file under the key "'
                    . $credential['config_key']
                    . '" in the connection "'
                    . $this->connectionKey
                    . '".'
                );
            }
        }
    }


    /**
     * @throws Exception
     */
    private function initialize(): void
    {
        try {
            $this->dbh = new PDO(
                $this->sculptDsn(),
                $this->credentials['username']['value'],
                $this->credentials['password']['value'],
                $this->options
            );

            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // ?
        } catch (PDOException $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @return string
     */
    private function sculptDsn(): string
    {
        return $this->localAndPdoDbmsDriversConformity[$this->credentials['dbms_driver']['value']]
            . ':host=' . $this->credentials['host']['value']
            . ';port=' . $this->credentials['port']['value']
            . ';dbname=' . $this->credentials['database']['value'];
    }
}
