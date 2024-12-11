<?php

namespace Moirai\DDL;

use Closure;
use Moirai\Drivers\MySqlDriver;

class Table
{
    /**
     * @param string $connection
     * @param string $table
     * @param \Closure|\Moirai\DDL\Blueprint $blueprint
     * @param bool $isTemporary
     * @param bool $ifNotExists
     * @return bool
     * @throws \Exception
     */
    public static function create(
        string $connection,
        string $table,
        Closure|Blueprint $blueprint,
        bool $isTemporary = false,
        bool $ifNotExists = false
    ): bool
    {
        if ($blueprint instanceof Closure) {
            $blueprint = new Blueprint(new MySqlDriver(), $table, $blueprint);
        }

        $statement = 'CREATE ';

        if ($isTemporary) {
            $statement .= 'TEMPORARY';
        }

        if ($ifNotExists) {
            $statement .= 'IF NOT EXISTS';
        }

        $statement .= $table . ' ('
            . implode(', ', $blueprint->getDefinitions())
            . '); '
            . implode('; ', $blueprint->getChainedStatements());

        return true;
    }

    public static function alter(string $connection, string $table, Closure|Blueprint $blueprint): void
    {
        /*
         * ALTER TABLE table_name ADD column_name column_definition;
         * ALTER TABLE table_name DROP COLUMN column_name;
         * ALTER TABLE table_name MODIFY COLUMN column_name new_definition;
         * ALTER TABLE table_name CHANGE COLUMN old_column_name new_column_name new_definition;
         */

        if ($blueprint instanceof Closure) {
            $blueprint = new Blueprint(new MySqlDriver(), $table, $blueprint);
        }

        foreach ($blueprint->getDefinitions() as $a) {

        }

        $statement = 'ALTER TABLE ' . $table . ' '
    }













    public static function drop(string $connection, string $table): bool
    {
        return true;
    }

    public static function truncate(string $connection, string $table): bool
    {
        /**
         * ALTER TABLE table_name TRUNCATE;
         */

        return true;
    }

    public static function lock(string $connection, string $table): bool
    {
        /**
         * ALTER TABLE table_name LOCK;
         */

        return true;
    }

    public static function unlock(string $connection, string $table): bool
    {
        /**
         * ALTER TABLE table_name UNLOCK;
         */

        return true;
    }

    public static function rename(string $connection, string $table): bool
    {
        /**
         * ALTER TABLE old_table_name RENAME TO new_table_name;
         */

        return true;
    }

    public static function changeEngine(string $connection, string $table): bool
    {
        /**
         * ALTER TABLE table_name ENGINE = new_storage_engine;
         */

        return true;
    }
}
