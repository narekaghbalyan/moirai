<?php

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
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'moirai_db',
            'db_username' => 'root',
            'db_password' => '',
            'db_driver' => AvailableDbmsDrivers::MYSQL
        ],
        'other_db_connection_example' => [
            'db_host' => 'other_db_connection_example_db_host',
            'db_port' => 'other_db_connection_example_db_port',
            'db_database' => 'other_db_connection_example_db_database',
            'db_username' => 'other_db_connection_example_db_username',
            'db_password' => 'other_db_connection_example_db_password',
            'db_driver' => AvailableDbmsDrivers::POSTGRESQL
        ]
    ],

];
