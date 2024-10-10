<?php

use Moirai\Connection\ConnectionProviders;
use Moirai\Drivers\AvailableDbmsDrivers;

return [
    /**
     * You can use one or more then one connection.
     *
     * In queries you can specify the connection.
     *
     * If you not pass connection key, "default"
     * connection will be used by default.
     */
    'connections' => [
        'default' => [
            'db_host' => '127.0.0.1', // required
            'db_port' => '3306', // required
            'db_database' => 'moirai_db', // required
            'db_username' => 'root', // not required
            'db_password' => '', // not required
            'db_driver' => AvailableDbmsDrivers::MYSQL, // not required, by default - MySQL
            'provider' => ConnectionProviders::PDO, // not required, by default - PDO
        ],
        // other connections ...
    ],
    /**
     * Persistent connections are not closed at the end of the script, but are
     * cached and re-used when another script requests a connection using the same
     * credentials. The persistent connection cache allows you to avoid the overhead
     * of establishing a new connection every time a script needs to talk to a
     * database, resulting in a faster web application.
     */
    'persistent' => true
];
