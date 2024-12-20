<?php

namespace Moirai\DDL;

use Closure;
use Moirai\Drivers\AvailableDbmsDrivers;
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
        $driver = new MySqlDriver();

        if ($blueprint instanceof Closure) {
            $blueprint = new Blueprint($driver, $table, Actions::CREATE, $blueprint);
        }

        $statement = 'CREATE TABLE '
            . $table
            . ' ('
            . implode(
                ', ',
                array_merge(
                    $blueprint->getColumnsDefinitions(),
                    $blueprint->getTableConstraintsDefinitions()
                )
            )
            . '); '
            . implode('; ', $blueprint->getChainedStatements());

        if ($ifNotExists) {
            if (in_array(
                $driver::class,
                [
                    AvailableDbmsDrivers::MYSQL,
                    AvailableDbmsDrivers::MARIADB,
                    AvailableDbmsDrivers::POSTGRESQL,
                    AvailableDbmsDrivers::SQLITE
                ]
            )) {
               $statement = str_replace(
                   'TABLE',
                   'TABLE IF NOT EXISTS',
                   $statement
               );
            } elseif ($driver::class === AvailableDbmsDrivers::MS_SQL_SERVER) {
                $statement = 'IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = \''
                    . $table
                    . '\' AND schema_id = SCHEMA_ID(\'dbo\')) BEGIN '
                    . $statement
                    . ' END;';
            } elseif ($driver::class === AvailableDbmsDrivers::ORACLE) {
                $statement = 'IF NOT EXISTS (SELECT * FROM user_tables WHERE table_name = \''
                    . $table
                    . '\' THEN EXECUTE IMMEDIATE \''
                    . rtrim($statement, ';')
                    . '\'; END IF; END;';
            }
        }

        if ($isTemporary) {
            if (in_array(
                $driver::class,
                [
                    AvailableDbmsDrivers::MYSQL,
                    AvailableDbmsDrivers::MARIADB,
                    AvailableDbmsDrivers::POSTGRESQL,
                    AvailableDbmsDrivers::SQLITE
                ]
            )) {
                $statement = str_replace(
                    'CREATE',
                    'CREATE TEMPORARY',
                    $statement
                );
            } elseif ($driver::class === AvailableDbmsDrivers::MS_SQL_SERVER) {
                $statement = str_replace(
                    $table,
                    '#' . $table,
                    $statement
                );
            } elseif ($driver::class === AvailableDbmsDrivers::ORACLE) {
                $statement = str_replace(
                    'CREATE',
                    'CREATE GLOBAL TEMPORARY',
                    $statement
                );
            }
        }

        return static::execute($statement);
    }

    /**
     * @throws \Exception
     */
    public static function alter(string $connection, string $table, Closure|Blueprint $blueprint): bool
    {
        $driver = new MySqlDriver();

        if ($blueprint instanceof Closure) {
            $blueprint = new Blueprint($driver, $table, Actions::CREATE, $blueprint);
        }

        $alterActions = $blueprint->getAlterActionsDefinitions();

        $baseStatement = 'ALTER TABLE ' . $table . ' ';

        $statements = [];

        if (in_array(
            $driver::class,
            [
                AvailableDbmsDrivers::MYSQL,
                AvailableDbmsDrivers::MARIADB,
                AvailableDbmsDrivers::POSTGRESQL
            ]
        )) {
            $statements[] = $baseStatement
                . implode(' ', [...$alterActions['add_columns_actions'], ...$alterActions['other_actions']])
                . ';';
        } elseif (in_array($driver::class, [AvailableDbmsDrivers::MS_SQL_SERVER, AvailableDbmsDrivers::ORACLE])) {
            $statements[] = $baseStatement . $alterActions['add_columns_actions'];

            foreach ($alterActions['other_actions'] as $otherAction) {
                $statements[] = $baseStatement . $otherAction;
            }
        } elseif ($driver::class === AvailableDbmsDrivers::SQLITE) {
            foreach ([...$alterActions['add_columns_actions'], ...$alterActions['other_actions']] as $action) {
                $statements[] = $baseStatement . $action;
            }
        }

        return static::execute(implode('; ', [...$statements, ...$blueprint->getChainedStatements()]));
    }

    public static function drop(string $connection, string $table): bool
    {
        return true;
    }

    private static function execute(string $statement): bool
    {
        return true;
    }
}
