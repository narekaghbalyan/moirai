<?php

namespace Moirai\Connection;

class Connection
{
    /**
     * Connection constructor.
     *
     * @param string $connectionKey
     */
    public function __construct(string $connectionKey = 'default')
    {
        $this->initialize($connectionKey);
    }

    /**
     * @param string $connectionKey
     */
    public function initialize(string $connectionKey)
    {
        $configs = include('configs.php');

        foreach ($configs['connections'] as $connection) {

        }
    }
}
