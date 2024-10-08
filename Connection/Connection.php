<?php

namespace Moirai\Connection;

use Exception;
use PDO;

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
     * @var array|string[]
     */
    private array $credentials = [
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
            'required' => true,
            'value' => null
        ],
        'password' => [
            'config_key' => 'db_password',
            'required' => false,
            'value' => ''
        ],
        'driver' => [
            'config_key' => 'db_driver',
            'required' => true,
            'value' => null
        ]
    ];

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

    /**
     * @throws Exception
     */
    private function applyCredentials()
    {
        foreach ($this->credentials as $key => $credential) {
            $this->credentials[$key]['value'] = $this->configs['connections'][$this->connectionKey][$credential['config_key']];

            if (!$credential['required']) {
                continue;
            }

            if (empty($this->configs['connections'][$this->connectionKey][$credential['config_key']])) {
                throw new Exception(
                    'The connection '
                    . $key
                    . ' could not be found or is empty. It should be in the configs file under the key "'
                    . $credential['config_key']
                    . '" in the connection "'
                    . $this->connectionKey
                    . '".'
                );
            }
        }
    }

    /**
     */
    public function initialize()
    {
        $dbh = new PDO($this->sculptDsn(), $user, $pass);

        dd();
    }

    /**
     * DSN - Data Source Name
     * The Data Source Name, contains the information required to connect to the database.
     *
     * @return string
     */
    private function sculptDsn(): string
    {
        // mysql:dbname=testdb;host=127.0.0.1
        return 'mysql:dbname=' . $this->configs[$this->credentials['database']['config_key']]
    }
}
