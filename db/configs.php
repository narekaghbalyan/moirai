<?php

use Moirai\Drivers\AvailableDbmsDrivers;

return [
    /**
     * Connections configs
     */

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
            'db_database' => 'moirai', // required
            'db_username' => 'root', // not required
            'db_password' => '', // not required
            'db_driver' => AvailableDbmsDrivers::MYSQL, // not required, MySQL by default
            /**
             * Controls whether persistent connections are used.
             *
             * Persistent connections are not closed at the end of the script, but are
             * cached and re-used when another script requests a connection using the same
             * credentials. The persistent connection cache allows you to avoid the overhead
             * of establishing a new connection every time a script needs to talk to a
             * database, resulting in a faster web application.
             *
             * If you not specify this option, it will be false by default.
             */
            'persistent' => true,

            /**
             * Determines whether prepared statement emulation is enabled or not.
             *
             * If you not specify this option, it will be true by default.
             */
            'emulate_prepares' => true,

            /**
             * Controls whether the connection will automatically commit after each SQL statement.
             *
             * If you not specify this option, it will be true by default.
             */
            'autocommit' => true,

            /**
             * Defines how column names are returned by default.
             *
             * If you not specify this option, it will be PDO::CASE_NATURAL by default.
             */
            'case' => PDO::CASE_NATURAL,

            /**
             * Defines the error reporting mode for PDO.
             *
             * If you not specify this option, it will be 0 by default.
             */
            'error_mode' => PDO::ERRMODE_SILENT,

            /**
             * Defines the default fetch mode for fetching results.
             *
             * If you not specify this option, it will be PDO::FETCH_ASSOC by default.
             */
            'default_fetch_mode' => PDO::FETCH_ASSOC,

            /**
             * Sets the connection timeout (in seconds).
             *
             * If you not specify this option, it will be null (no timeout) by default.
             */
            'timeout' => null,

            /**
             * Defines the cursor type for fetching results.
             *
             * If you not specify this option, it will be 0 by default.
             */
            'cursor' => PDO::CURSOR_FWDONLY,

            /**
             * Specifies the class to use for PDO statements.
             *
             * If you not specify this option, it will be null (no custom statement class) by default.
             */
            'statement_class' => null
        ],
        // 'example_for_sqlite' => [
        //     'db_file_path' => 'full_path',
        //     'db_driver' => AvailableDbmsDrivers::SQLITE
        //  ],
        // other connections ...
    ],

    /**
     * DDL configs
     */

    'migrations_path' => __DIR__ . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'migrations',
    'models_path' => __DIR__ . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'models'

//    'nullable' => false // by default all columns are nullable
];
