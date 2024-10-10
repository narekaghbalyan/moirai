<?php

namespace Moirai\Drivers;

use ReflectionClass;

class AvailableDbmsDrivers
{
    const MYSQL = 'mysql';
    const POSTGRESQL = 'postgresql';
    const SQLITE = 'sqlite';
    const MS_SQL_SERVER = 'microsoft sql server';
    const MARIADB = 'mariadb';
    const ORACLE = 'oracle';

    /**
     * @return array
     */
    public static function getDrivers(): array
    {
        $reflectionClass = new ReflectionClass(__CLASS__);

        return $reflectionClass->getConstants();
    }

//    public static function getDriversAndHandlersConformity(): array
//    {
//        return [
//            self::MYSQL => MySqlDriver::class,
//            self::POSTGRESQL => null
//        ];
//    }
}
