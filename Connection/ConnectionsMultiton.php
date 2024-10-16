<?php

namespace Moirai\Connection;

use Exception;

class ConnectionsMultiton
{
    /**
     * @var array
     */
    private static array $instances = [];

    /**
     * Connection constructor.
     */
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a connection.');
    }

    /**
     * @param string $connectionKey
     * @return \Moirai\Connection\Connection
     * @throws \Exception
     */
    public static function getInstance(string $connectionKey): Connection
    {
        return static::$instances[$connectionKey]
            ?? (static::$instances[$connectionKey] = new Connection($connectionKey));
    }
}