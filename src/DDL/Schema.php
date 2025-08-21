<?php

namespace Moirai\DDL;

use Closure;
use Exception;
use Moirai\Connection\Connections;
use Moirai\DDL\Shared\Actions;
use Moirai\Drivers\AvailableDbmsDrivers;

class Schema
{
    /**
     * @param string $connectionKey
     * @param string $table
     * @param \Closure|\Moirai\DDL\Blueprint $blueprint
     * @param bool $isTemporary
     * @param bool $ifNotExists
     * @return bool
     * @throws \Exception
     */
    public static function create(
        string $connectionKey,
        string $table,
        Closure|Blueprint $blueprint,
        bool $isTemporary = false,
        bool $ifNotExists = false
    ): bool {
        $connectionInstance = Connections::getInstance($connectionKey);

        $driver = $connectionInstance->getDbmsDriverInstance();

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

        return static::execute($connectionInstance, $statement);
    }

    /**
     * @param string $connectionKey
     * @param string $table
     * @param \Closure|\Moirai\DDL\Blueprint $blueprint
     * @return bool
     * @throws \Exception
     */
    public static function alter(string $connectionKey, string $table, Closure|Blueprint $blueprint): bool
    {
        $connectionInstance = Connections::getInstance($connectionKey);

        $driver = $connectionInstance->getDbmsDriverInstance();

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

        return static::execute(
            $connectionInstance,
            implode('; ', [...$statements, ...$blueprint->getChainedStatements()])
        );
    }

    /**
     * @param string $connectionKey
     * @param string $table
     * @param bool $ifExists
     * @param bool $cascade
     * @return bool
     * @throws \Exception
     */
    public static function drop(string $connectionKey, string $table, bool $ifExists = false, bool $cascade = false): bool
    {
        $connectionInstance = Connections::getInstance($connectionKey);

        $driver = $connectionInstance->getDbmsDriverInstance();

        $cascadeDefinition = $cascade ? match ($driver::class) {
            AvailableDbmsDrivers::POSTGRESQL => ' CASCADE',
            AvailableDbmsDrivers::ORACLE => ' CASCADE CONSTRAINTS',
            default => throw new Exception('CASCADE is not supported for this database driver'),
        } : '';

        $statement = match ($driver::class) {
            AvailableDbmsDrivers::MYSQL,
            AvailableDbmsDrivers::MARIADB,
            AvailableDbmsDrivers::POSTGRESQL,
            AvailableDbmsDrivers::SQLITE => ($ifExists ? 'DROP TABLE IF EXISTS ' : 'DROP TABLE ') . $table . $cascadeDefinition . ';',
            AvailableDbmsDrivers::MS_SQL_SERVER => 'IF OBJECT_ID(\'' . $table . '\', \'U\') IS NOT NULL DROP TABLE ' . $table . ';',
            AvailableDbmsDrivers::ORACLE => 'BEGIN EXECUTE IMMEDIATE \'DROP TABLE ' . $table . $cascadeDefinition . '\'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;',
            default => throw new Exception('Unsupported database driver'),
        };

        return static::execute($connectionInstance, $statement);
    }

    /**
     * @param \Moirai\Connection\Connections $connectionInstance
     * @param string $statement
     * @return bool
     */
    private static function execute(Connections $connectionInstance, string $statement): bool
    {
        return $connectionInstance->getDbh()->execute($statement);
    }
}
