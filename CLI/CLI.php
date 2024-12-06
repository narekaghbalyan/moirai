<?php

namespace Moirai\CLI;

class CLI
{
    /**
     * @var string
     */
    private static string $migrationsDestinationPath = __DIR__ . '/Tables/';

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
            echo 'Error: Action "'
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
            self::$migrationsDestinationPath . $table . '.php',
            '<?php'
            . PHP_EOL
        );

        echo 'Migration for table "' . $table . '" successfully created.' . PHP_EOL;
    }

    private static function alter(string $table)
    {

    }

    private static function drop(string $table)
    {

    }

    private static function migrate(string|null $table)
    {

    }

    private static function rollback(string|null $table)
    {

    }
}

CLI::run($argv);