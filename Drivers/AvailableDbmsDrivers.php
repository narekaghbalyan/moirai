<?php

namespace Moirai\Drivers;

use ReflectionClass;

class AvailableDbmsDrivers
{
    const MYSQL = 0;
    const POSTGRESQL = 1;
    const SQLITE = 2;
    const MS_SQL_SERVER = 3;
    const MARIADB = 4;
    const ORACLE = 5;

    /**
     * @return array
     */
    public static function getDrivers(): array
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }
}
