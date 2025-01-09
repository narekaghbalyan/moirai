<?php

namespace Moirai\CLI;

require_once('../vendor/autoload.php');

use DateTime;
use Moirai\DDL\Actions;
use Moirai\DDL\Migration\Templates\Templates;

class CLI
{
    /**
     * @var string
     */
    private static string $migrationsDestinationPath;

    /**
     * @var string
     */
    private static string $prefixForSuccessMessages = '[+]';

    /**
     * @var string
     */
    private static string $prefixForFailedMessages = '[-]';

    /**
     * php moirai create <table_name>
     * php moirai alter <table_name>
     * php moirai drop <table_name>
     * php moirai migrate
     * php moirai rollback
     *
     * @param array $argv
     */
    public static function run(array $argv): void
    {
        $action = $argv[1] ?? null;

        if (!method_exists(self::class, $action)) {
            echo static::$prefixForFailedMessages
                . ' Error: Action "'
                . $action
                . '" is not recognized for command "'
                . implode(' ', $argv)
                . '".'
                . PHP_EOL;

            die();
        }

        static::$migrationsDestinationPath = str_replace('CLI', '', __DIR__)
            . 'Migrations'
            . DIRECTORY_SEPARATOR;

        self::$action($argv[2] ?? null);
    }

    /**
     * @param string $table
     */
    private static function create(string $table)
    {
        file_put_contents(
            self::sculptMigrationName($table, Actions::CREATE),
            Templates::getForCreate($table)
        );

        echo static::$prefixForSuccessMessages
            . ' Migration for creating table "'
            . $table
            . '" successfully created.'
            . PHP_EOL;
    }

    /**
     * @param string $table
     */
    private static function alter(string $table)
    {
        file_put_contents(
            self::sculptMigrationName($table, Actions::ALTER),
            Templates::getForAlter($table)
        );

        echo static::$prefixForSuccessMessages
            . ' Migration for altering table "'
            . $table
            . '" successfully created.'
            . PHP_EOL;
    }

    /**
     * @param string $table
     */
    private static function drop(string $table)
    {
        file_put_contents(
            self::sculptMigrationName($table, Actions::DROP),
            Templates::getForDrop($table)
        );

        echo static::$prefixForSuccessMessages
            . ' Migration for dropping table "'
            . $table
            . '" successfully created.'
            . PHP_EOL;
    }

    /**
     *
     */
    private static function migrate(): void
    {
        foreach (static::getSortedMigrationFiles('desc') as $migration) {
            $migrationClass = require_once $migration;

            if (!$migrationClass->onMigrate()) {
                echo static::$prefixForFailedMessages . ' Migration "' . $migration . '" failed.' . PHP_EOL;

                continue;
            }

            echo static::$prefixForSuccessMessages . ' Migration "' . $migration . '" were migrated successfully.' . PHP_EOL;
        }

        echo static::$prefixForSuccessMessages . ' All migrations were migrated successfully.' . PHP_EOL;
    }

    /**
     *
     */
    private static function rollback()
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

    /**
     * @param string $direction
     * @return array
     */
    private static function getSortedMigrationFiles(string $direction = 'asc'): array
    {
        $migrations = glob(static::$migrationsDestinationPath . '*.php');

        $order = $direction === 'desc' ? -1 : 1;

        usort($migrations, function ($a, $b) use ($order) {
            return $order * DateTime::createFromFormat(
                    'd-m-Y-H-i-s',
                    substr(
                        pathinfo($a, PATHINFO_FILENAME),
                        0,
                        19
                    )
                )
                <=> DateTime::createFromFormat(
                    'd-m-Y-H-i-s',
                    substr(
                        pathinfo($b, PATHINFO_FILENAME),
                        0,
                        19
                    )
                );
        });

        return $migrations;
    }

    /**
     * @param string $table
     * @param string $action
     * @return string
     */
    private static function sculptMigrationName(string $table, string $action): string
    {
        return static::$migrationsDestinationPath
            . date("d-m-Y-H-i-s")
            . '-'
            . $action
            . '-'
            . $table
            . '-table'
            . '.php';
    }
}

CLI::run($argv);