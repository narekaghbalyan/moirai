<?php

namespace Moirai\DDL;

use Closure;
use Moirai\Connection\Connections;
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
            . implode(', ', array_merge($blueprint->sewColumns(), $blueprint->sewTableConstraints()))
            . '); '
            . implode('; ', $blueprint->getChainedStatements());

        return static::execute($statement);
    }


    /**
     * @throws \Exception
     */
    public static function alter(string $connection, string $table, Closure|Blueprint $blueprint): void
    {
        if ($blueprint instanceof Closure) {
            $blueprint = new Blueprint(new MySqlDriver(), $table, $blueprint);
        }

        $statement = 'ALTER TABLE ' . $table . ' ';

        foreach ($blueprint->sewColumns() as $column => $columnDefinition) {
            if (in_array($column, $blueprint->modify)) {
                $statement .= 'MODIFY COLUMN ' . $columnDefinition;

                continue;
            }

            if (!in_array($column, array_keys($blueprint->rename))) {
                $statement .= 'CHANGE COLUMN '
                    . $column
                    . ' '
                    . $blueprint->rename[$column]
                    . ' '
                    . $columnDefinition
                    . ', ';

                continue;
            }

            $statement .= 'ADD COLUMN ' . $columnDefinition . ', ';
        }

        /**
         * ALTER TABLE table_name ADD PRIMARY KEY (column_name);
         * ALTER TABLE table_name DROP PRIMARY KEY;
         * ALTER TABLE table_name ADD UNIQUE (column_name);
         * ALTER TABLE employees ADD UNIQUE (email);
         * ALTER TABLE table_name DROP INDEX index_name;
         *
         * ALTER TABLE table_name
         * ADD CONSTRAINT constraint_name FOREIGN KEY (column_name)
         * REFERENCES other_table (other_column)
         * ON DELETE action ON UPDATE action;
         *
         * ALTER TABLE table_name
         * DROP FOREIGN KEY constraint_name;
         *
         * ALTER TABLE sales
         * PARTITION BY RANGE (year)
         * (
         * PARTITION p0 VALUES LESS THAN (2000),
         * PARTITION p1 VALUES LESS THAN (2010)
         * );
         *
         *
         *
         * ALTER TABLE employees
         * ADD CONSTRAINT check_salary CHECK (salary > 0);
         *
         *
         * ALTER TABLE table_name
         * AUTO_INCREMENT = new_value;
         */

        foreach ($blueprint->sewTableConstraints() as $column => $columnDefinition) {

        }


        foreach ($blueprint->drop as $column) {
            $statement .= 'DROP COLUMN ' . $column;
        }

    }































    /**
     * @param string $connection
     * @param string $table
     * @param string $newName
     * @return bool
     * @throws \Exception
     */
    public static function rename(string $connection, string $table, string $newName): bool
    {
        $driver = new MySqlDriver();

        return static::execute(
            'ALTER TABLE '
            . str_replace(
                ['{name}', '{new_name}'],
                [$table, $newName],
                $driver->getLexis()->getAlterAction(AlterActions::RENAME_TABLE)
            )
        );
    }

    /**
     * @param string $connection
     * @param string $table
     * @param string $engine
     * @return bool
     * @throws \Exception
     */
    public static function changeEngine(string $connection, string $table, string $engine): bool
    {
        $driver = new MySqlDriver();

        return static::execute(
            'ALTER TABLE '
            . str_replace(
                ['{table}', '{engine}'],
                [$table, $engine],
                $driver->getLexis()->getAlterAction(AlterActions::CHANGE_ENGINE)
            )
        );
    }

    /**
     * @param string $connection
     * @param string $table
     * @param int|string $value
     * @return bool
     * @throws \Exception
     */
    public static function changeAutoincrement(string $connection, string $table, int|string $value): bool
    {
        $driver = new MySqlDriver();

        return static::execute(
            'ALTER TABLE '
            . str_replace(
                ['{table}', '{value}'],
                [$table, $value],
                $driver->getLexis()->getAlterAction(AlterActions::CHANGE_AUTO_INCREMENT)
            )
        );
    }

    private static function execute(string $statement): bool
    {
        return true;
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
}
