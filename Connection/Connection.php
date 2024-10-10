<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Drivers\AvailableDbmsDrivers;
use PDO;

abstract class Connection
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
     * @var array|string[]
     */
    protected array $credentials = [
        'host' => [
            'config_key' => 'db_host',
            'required' => true,
            'value' => null
        ],
        'port' => [
            'config_key' => 'db_port',
            'required' => true,
            'value' => null
        ],
        'database' => [
            'config_key' => 'db_database',
            'required' => true,
            'value' => null
        ],
        'username' => [
            'config_key' => 'db_username',
            'required' => false,
            'value' => ''
        ],
        'password' => [
            'config_key' => 'db_password',
            'required' => false,
            'value' => ''
        ],
        'driver' => [
            'config_key' => 'db_driver',
            'required' => true,
            'value' => AvailableDbmsDrivers::MYSQL
        ],
        'provider' => [
            'config_key' => 'provider',
            'required' => true,
            'value' => ConnectionProviders::PDO
        ]
    ];

    /**
     * DBH - Database Handle
     *
     * @var mixed
     */
    protected mixed $dbh;

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

        $this->validate();

        $this->applyCredentials();

        $this->initialize();
    }

    /**
     * @throws Exception
     */
    private function validate()
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
    }

    private function resolveProvider()
    {

    }

    /**
     * @throws Exception
     */
    private function applyCredentials()
    {
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
     *
     */
    abstract public function initialize(): void;

    /**
     * DSN - Data Source Name
     * The Data Source Name, contains the information required to connect to the database.
     *
     * @return string
     */
    abstract protected function sculptDsn(): string;


    public function disconnect(): void
    {
        $this->dbh = null;
    }
}
