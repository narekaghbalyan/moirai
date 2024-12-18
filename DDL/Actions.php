<?php

namespace Moirai\DDL;

use ReflectionClass;

class Actions
{
    public const CREATE = 'create';
    public const ALTER = 'alter';
    public const DROP = 'drop';

    /**
     * @return array
     */
    public static function getActions(): array
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }
}