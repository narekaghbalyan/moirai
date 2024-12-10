<?php

namespace Moirai\CLI;

use DateTime;
use Moirai\DDL\Actions;

class CLI
{
    /**
     * @var string
     */
    private static string $migrationsDestinationPath;

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
        static::$migrationsDestinationPath = str_replace('CLI', '', __DIR__)
            . 'Migrations'
            . DIRECTORY_SEPARATOR;

        $action = $argv[1] ?? null;

        if (!method_exists(self::class, $action)) {
            echo '[-] Error: Action "'
                . $action
                . '" is not recognized for command "'
                . implode(' ', $argv)
                . '".'
                . PHP_EOL;

            die();
        }

        self::$action($argv[2] ?? null);
    }

    /**
     * @param string $table
     */
    private static function create(string $table)
    {
        file_put_contents(
            self::sculptMigrationName($table, 'create'),
            '<?php'
            . PHP_EOL
        );

        echo '[+] Migration for table "' . $table . '" successfully created.' . PHP_EOL;
    }

    private static function alter(string $table)
    {

    }

    private static function drop(string $table)
    {

    }












    private static function migrate(): void
    {
        $migrations = glob(static::$migrationsDestinationPath . '*.php');

        usort($migrations, function ($a, $b) {
            return DateTime::createFromFormat(
                    'd-m-Y-H-i-s',
                    substr(
                        pathinfo($b, PATHINFO_FILENAME),
                        0,
                        19
                    )
                )
                <=> DateTime::createFromFormat(
                    'd-m-Y-H-i-s',
                    substr(
                        pathinfo($a, PATHINFO_FILENAME),
                        0,
                        19
                    )
                );
        });

        foreach ($migrations as $migration) {
            $migrationClass = require_once $migration;

            if (!$migrationClass->onMigrate()) {
                echo '[-] Migration "' . $migration . '" failed.' . PHP_EOL;

                continue;
            }

            echo '[+] Migration "' . $migration . '" migrated successfully.' . PHP_EOL;
        }

        echo '[+] All migrations migrates successfully.' . PHP_EOL;
    }














    private static function rollback(string|null $table)
    {

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