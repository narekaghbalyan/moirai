<?php

namespace Moirai\CLI;

require_once('../vendor/autoload.php');

use Moirai\CLI\Traits\CLIToolkit;

class CLI
{
    use CLIToolkit;

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

        self::$action($argv[2] ?? null);
    }
}

CLI::run($argv);
