<?php

namespace Moirai\DDL\Migration\Templates;

class Templates
{
    /**
     * @param string $table
     * @return string
     */
    public static function getForCreate(string $table): string
    {
        return static::sculpt('creating', $table, 'Create');
    }

    /**
     * @param string $table
     * @return string
     */
    public static function getForAlter(string $table): string
    {
        return static::sculpt('altering', $table, 'Alter');
    }

    /**
     * @param string $table
     * @return string
     */
    public static function getForDrop(string $table): string
    {
        return static::sculpt('dropping', $table, 'Drop');
    }

    /**
     * @param string $migrationActionComment
     * @param string $table
     * @param string $snippetsFolder
     * @return string
     */
    private static function sculpt(string $migrationActionComment, string $table, string $snippetsFolder): string
    {
        $snippetsBasePath = __DIR__ . DIRECTORY_SEPARATOR . 'Snippets' . DIRECTORY_SEPARATOR;

        return str_replace(
            [
                '{migration_action_comment}',
                '{table}',
                '{on_migrate_action}',
                '{on_rollback_action}'
            ],
            [
                $migrationActionComment,
                $table,
                trim(
                    require_once($snippetsBasePath . $snippetsFolder . DIRECTORY_SEPARATOR . 'OnMigrateInnerSnippet.php')
                ),
                trim(
                    require_once($snippetsBasePath . $snippetsFolder . DIRECTORY_SEPARATOR . 'OnRollbackInnerSnippet.php')
                )
            ],
            trim(require_once(__DIR__ . DIRECTORY_SEPARATOR . 'BaseTemplate.php'))
        );
    }
}