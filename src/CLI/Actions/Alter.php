<?php

namespace Moirai\CLI\Actions;

use Moirai\DDL\Migration\Templates\Templates;
use Moirai\DDL\Shared\Actions;

class Alter extends Action
{
    /**
     * @param array $context
     * @return bool
     */
    public static function dispatch(array $context = []): bool
    {
        if (!file_put_contents(
            self::sculptMigrationName($context['table'], Actions::ALTER),
            Templates::getForAlter($context['table'])
        )) {
            echo static::$prefixForFailedMessages
                . ' Creating migration for altering table "'
                . $context['table']
                . '" failed.'
                . PHP_EOL;

            return false;
        }

        echo static::$prefixForSuccessMessages
            . ' Migration for altering table "'
            . $context['table']
            . '" successfully created.'
            . PHP_EOL;

        return true;
    }
}
