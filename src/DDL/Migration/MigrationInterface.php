<?php

namespace Moirai\DDL\Migration;

interface MigrationInterface
{
    /**
     * @return bool
     */
    public function onMigrate(): bool;

    /**
     * @return bool
     */
    public function onRollback(): bool;
}