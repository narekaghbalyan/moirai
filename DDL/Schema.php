<?php

namespace Moirai\DDL;

use Closure;

class Schema
{
    /**
     * @param string $table
     * @param \Closure|\Moirai\DDL\Blueprint $blueprint
     * @return bool
     * @throws \Exception
     */
    public static function create(string $table, Closure|Blueprint $blueprint): bool
    {
        if ($blueprint instanceof Closure) {
            $blueprint = new Blueprint($table, $blueprint);
        }

        $statement = 'CREATE TABLE (' . $blueprint->sew() . ');';

        return true;
    }

    public static function update(): void
    {

    }

    public static function drop(): bool
    {
        return true;
    }
}
