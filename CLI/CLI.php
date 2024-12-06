<?php

namespace Moirai\CLI;

class CLI
{
    /**
     * php moirai table:create users
     * php moirai table:alert users
     * php moirai table:update users
     * php moirai table:drop users
     * php moirai table:delete users
     * php moirai table:migrate users
     *
     * @param array $argv
     */
    public static function run(array $argv): void
    {
        $command = $argv[1] ?? null;

        switch ($command) {
            case 'table:create':
            case 'table:new':
                self::createTable($argv);

                break;
            case 'table:alert':
            case 'table:update':
                self::alertTable($argv);

                break;
            case 'table:drop':
            case 'table:delete':
                self::dropTable($argv);

                break;
            case 'table:migrate':


                break;
            default:
                echo 'Error: Command "' . $command . '" not recognized.\\n';
        }
    }

    private static function createTable(array $arguments)
    {

    }

    private static function alertTable(array $arguments)
    {

    }

    private static function dropTable(array $arguments)
    {

    }
}

CLI::run($argv);