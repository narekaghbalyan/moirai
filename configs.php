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
     *
     * Not required parameters must be declared.
     *
     * For SQLite you must specify database file full path under key
     * db_file_path. For SQLite you can use only db_file_path and
     * db_driver attributes, other attributes will be ignored.
     * If you use db_file_path attribute you must specify db_driver as
     * SQLite (AvailableDbmsDrivers::SQLITE), because that attribute work
     * only for SQLite. If you specify that attribute for other drivers,
     * exception will be thrown.
     */
    'connections' => [
        'default' => [
            'db_host' => '127.0.0.1', // required
            'db_port' => '3306', // not required, 3306 by default
            'db_database' => 'moirai_db', // required
            'db_username' => 'root', // not required
            'db_password' => '', // not required
            'db_driver' => AvailableDbmsDrivers::MYSQL // not required, MySQL by default
        ],
        // 'example_for_sqlite' => [
        //     'db_file_path' => 'full_path',
        //     'db_driver' => AvailableDbmsDrivers::SQLITE
        //  ],
        // other connections ...
    ],
    /**
     * Persistent connections are not closed at the end of the script, but are
     * cached and re-used when another script requests a connection using the same
     * credentials. The persistent connection cache allows you to avoid the overhead
     * of establishing a new connection every time a script needs to talk to a
     * database, resulting in a faster web application.
     *
     * If you not specify this option, it will be true by default.
     */
    'persistent' => true
];
