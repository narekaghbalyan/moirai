<?php

namespace Moirai\CLI\Actions;

class Migrate extends Action
{
    /**
     * @param array $context
     * @return bool
     */
    public static function dispatch(array $context = []): bool
    {
        foreach (static::getSortedMigrationFiles('desc') as $migration) {
            $migrationClass = require_once $migration;

            if (!$migrationClass->onMigrate()) {
                echo static::$prefixForFailedMessages . ' Migration "' . $migration . '" failed.' . PHP_EOL;

                continue;
            }

            echo static::$prefixForSuccessMessages . ' Migration "' . $migration . '" were migrated successfully.' . PHP_EOL;
        }

        // TODO: Add logic to handle only not migrated files

        echo static::$prefixForSuccessMessages . ' All migrations were migrated successfully.' . PHP_EOL;

        return true;
    }
}
