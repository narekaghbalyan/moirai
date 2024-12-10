<?php

namespace Moirai\Migrations;

use Moirai\DDL\Migration;
use Moirai\DDL\Schema;
use Moirai\DDL\Blueprint;

/**
 * Migration for creating users table
 */
return new class extends Migration
{
    /**
     * Do something during migrate.
     *
     * @return bool
     */
    public function onMigrate(): bool
    {
        return Schema::create('users', function (Blueprint $blueprint) {
            $blueprint->integer('id', true, true);
            $blueprint->varchar('name')->notNull();
        });
    }

    /**
     * Do something during rollback.
     *
     * @return bool
     */
    public function onRollback(): bool
    {
        return Schema::drop();
    }
};