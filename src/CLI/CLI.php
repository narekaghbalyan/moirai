<?php

namespace Moirai\CLI;

require_once('../vendor/autoload.php');

use Moirai\CLI\Actions\Alter;
use Moirai\CLI\Actions\Create;
use Moirai\CLI\Actions\Drop;
use Moirai\CLI\Actions\Migrate;
use Moirai\CLI\Actions\Rollback;
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
        $actionsMap = [
            'create' => Create::class,
            'alter' => Alter::class,
            'drop' => Drop::class,
            'migrate' => Migrate::class,
            'rollback' => Rollback::class
        ];

        $action = $argv[1] ?? null;

        if (!in_array($action, $actionsMap)) {
            echo static::$prefixForFailedMessages
                . ' Error: Action "'
                . $action
                . '" is not recognized for command "'
                . implode(' ', $argv)
                . '".'
                . PHP_EOL;

            die();
        }

        $action::dispatch($argv[2] ?? []);
    }
}

CLI::run($argv);
