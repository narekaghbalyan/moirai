<?php

namespace Moirai\CLI\Actions;

use Moirai\DDL\Migration\Templates\Templates;
use Moirai\DDL\Shared\Actions;

class Create extends Action
{
    /**
     * @param array $context
     * @return bool
     */
    public static function dispatch(array $context = []): bool
    {
        if (!file_put_contents(
            self::sculptMigrationName($context['table'], Actions::CREATE),
            Templates::getForCreate($context['table'])
        )) {
            echo static::$prefixForFailedMessages
                . ' Creating migration for creating table "'
                . $context['table']
                . '" failed.'
                . PHP_EOL;

            return false;
        }

        echo static::$prefixForSuccessMessages
            . ' Migration for creating table "'
            . $context['table']
            . '" successfully created.'
            . PHP_EOL;

        return true;
    }
}
