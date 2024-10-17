<?php

namespace Moirai\DDL;

use Closure;

class Schema extends SchemaBuilder
{
    /**
     * @param string $table
     * @param \Closure|\Moirai\DDL\Blueprint $blueprint
     */
    public static function create(string $table, Closure|Blueprint $blueprint): void
    {
        if (!$blueprint instanceof Blueprint) {
            $blueprint = new Blueprint($table, $blueprint);
        }

        $tableCreationExpression = 'CREATE TABLE (' . $blueprint . ')';

        dd($tableCreationExpression);
    }
}
