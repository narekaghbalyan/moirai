<?php

namespace Moirai\CLI\Actions;

use DateTime;
use Moirai\CLI\Traits\CLIToolkit;

abstract class Action
{
    use CLIToolkit;

    /**
     * @var string
     */
    private static string $migrationsDestinationPath;

    public function __construct()
    {
        static::$migrationsDestinationPath = str_replace('CLI', '', __DIR__)
            . 'Migrations'
            . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $direction
     * @return array
     */
    protected static function getSortedMigrationFiles(string $direction = 'asc'): array
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
    protected static function sculptMigrationName(string $table, string $action): string
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

    /**
     * @param array $context
     */
    abstract public static function dispatch(array $context = []): bool;
}
