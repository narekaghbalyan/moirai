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
        return static::sculpt('dropping', $table, 'Drop', false);
    }

    /**
     * @param string $migrationActionComment
     * @param string $table
     * @param string $snippetsFolder
     * @param bool $includeBlueprintNamespace
     * @return string
     */
    private static function sculpt(
        string $migrationActionComment,
        string $table,
        string $snippetsFolder,
        bool $includeBlueprintNamespace = true
    ): string
    {
        $snippetsBasePath = __DIR__ . DIRECTORY_SEPARATOR . 'Snippets' . DIRECTORY_SEPARATOR;

        $blueprintNamespacePlaceholder = '{blueprint_namespace}';
        $blueprintNamespaceReplacement = 'use Moirai\DDL\Blueprint;';

        if (!$includeBlueprintNamespace) {
            $blueprintNamespacePlaceholder .= PHP_EOL;
            $blueprintNamespaceReplacement = '';
        }

        return str_replace(
            [
                $blueprintNamespacePlaceholder,
                '{migration_action_comment}',
                '{table}',
                '{on_migrate_action}',
                '{on_rollback_action}'
            ],
            [
                $blueprintNamespaceReplacement,
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