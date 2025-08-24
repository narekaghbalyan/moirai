<?php

namespace Moirai\CLI\Actions;

class Help extends Action
{
    /**
     * @param array $context
     * @return bool
     */
    public static function dispatch(array $context = []): bool
    {
        echo 'php moirai create <table_name>'. PHP_EOL
            . 'php moirai alter <table_name>' . PHP_EOL
            . 'php moirai drop <table_name>' . PHP_EOL
            . 'php moirai migrate' . PHP_EOL
            . 'php moirai rollback';

        return true;
    }
}
