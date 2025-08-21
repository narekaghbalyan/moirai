<?php

namespace Moirai\Drivers;

use ReflectionClass;

class AvailableDbmsDrivers
{
    const MYSQL = MySqlDriver::class;
    const MARIADB = MariaDbDriver::class;
    const POSTGRESQL = PostgreSqlDriver::class;
    const MS_SQL_SERVER = MsSqlServerDriver::class;
    const ORACLE = OracleDriver::class;
    const SQLITE = SqliteDriver::class;

    /**
     * @return array
     */
    public static function getDrivers(): array
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }
}
