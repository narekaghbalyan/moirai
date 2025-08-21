<?php

namespace Moirai\CLI\Actions;

class Rollback extends Action
{
    /**
     *
     */
    private static function dispatch()
    {
        foreach (static::getSortedMigrationFiles() as $migration) {
            $migrationClass = require_once $migration;

            if (!$migrationClass->onRollback()) {
                echo static::$prefixForFailedMessages . ' Migration "' . $migration . '" rollback failed.' . PHP_EOL;

                continue;
            }

            echo static::$prefixForSuccessMessages . ' Migration "' . $migration . '" rolled back successfully.' . PHP_EOL;
        }

        echo static::$prefixForSuccessMessages . ' All migrations were rolled back successfully.' . PHP_EOL;
    }
}
