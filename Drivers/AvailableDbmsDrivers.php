<?php

namespace Moarai\Drivers;

use ReflectionClass;

class AvailableDbmsDrivers
{
    const MYSQL = 'mysql';

    const POSTGRESQL = 'postgresql';

//    public static function getDriversAndHandlersConformity(): array
//    {
//        return [
//            self::MYSQL => MySqlDriver::class,
//            self::POSTGRESQL => null
//        ];
//    }

    public static function getAllDrivers(): array
    {
        $reflectionClass = new ReflectionClass(__CLASS__);

        return $reflectionClass->getConstants();
    }
}