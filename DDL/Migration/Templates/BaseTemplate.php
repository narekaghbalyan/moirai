<?php

return <<<PHP
<?php

namespace Moirai\Migrations;

use Moirai\DDL\Migration\Migration;
use Moirai\DDL\Table;
use Moirai\DDL\Blueprint;

/**
 * Migration for {migration_action_comment} "{table}" table.
 */
return new class extends Migration
{
    /**
     * Connection key (from configs.php file).
     *
     * @var string
     */
    protected string \$connection = 'default';

    /**
     * Table name.
     *
     * @var string
     */
    protected string \$table = '{table}';

    /**
     * Action to be performed during migration.
     *
     * php moirai migrate
     * php moirai migrate -t={table}
     * php moirai migrate --table={table}
     *
     * The -t (or --table) argument is an optional argument, if not
     * specified, all migrations will be migrated, and if a specific table is
     * specified, then only that one will be migrated.
     *
     * @return bool
     * @throws \Exception
     */
    public function onMigrate(): bool
    {
        {on_migrate_action}
    }

    /**
     * Action to be performed when rolling back a migration.
     *
     * php moirai rollback
     * php moirai rollback -t={table}
     * php moirai rollback --table={table}
     * 
     * The -t (or --table) argument is an optional argument, if not
     * specified, all migrations will be rolled back, and if a specific table
     * is specified, only that one will be rolled back.
     *
     * @return bool
     */
    public function onRollback(): bool
    {
        {on_rollback_action}
    }
};
PHP;