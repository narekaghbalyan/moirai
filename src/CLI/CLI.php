<?php

namespace Moirai\CLI;

use Moirai\CLI\Actions\Create;
use Moirai\CLI\Actions\Alter;
use Moirai\CLI\Actions\Drop;
use Moirai\CLI\Actions\Migrate;
use Moirai\CLI\Actions\Rollback;
use Moirai\CLI\Actions\Help;
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
     * @return bool
     */
    public static function run(array $argv): bool
    {
        $actionsMap = [
            'create' => [
                'class' => Create::class,
                'context_key' => 'table'
            ],
            'alter' => [
                'class' => Alter::class,
                'context_key' => 'table'
            ],
            'drop' => [
                'class' => Drop::class,
                'context_key' => 'table'
            ],
            'migrate' => [
                'class' => Migrate::class
            ],
            'rollback' => [
                'class' => Rollback::class
            ],
            'help' => [
                'class' => Help::class
            ]
        ];

        $action = $argv[1] ?? null;

        if (!array_key_exists($action, $actionsMap)) {
            echo static::$prefixForFailedMessages
                . ' Error: Action "'
                . $action
                . '" is not recognized for command "'
                . implode(' ', $argv)
                . '".'
                . PHP_EOL;

            exit();
        }

        $context = [];

        if (isset($argv[2]) && isset($actionsMap[$action]['context_key'])) {
            $context = [$actionsMap[$action]['context_key'] => $argv[2]];
        }

        return $actionsMap[$action]['class']::dispatch($context);
    }
}
