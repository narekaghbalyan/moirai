<?php

namespace Moarai\Drivers;

use ReflectionClass;

class AvailableDbmsDrivers
{
    const MYSQL = 'mysql';

    const POSTGRESQL = 'postgresql';

    const SQLITE = 'sqlite';

//    public static function getDriversAndHandlersConformity(): array
//    {
//        return [
//            self::MYSQL => MySqlDriver::class,
//            self::POSTGRESQL => null
//        ];
//    }

    public static function getDrivers(): array
    {
        $reflectionClass = new ReflectionClass(__CLASS__);

        return $reflectionClass->getConstants();
    }
}