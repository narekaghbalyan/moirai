<?php

namespace Moirai\DDL;

use Closure;

class Schema
{
    /**
     * @param string $table
     * @param \Closure|\Moirai\DDL\Blueprint $blueprint
     * @throws \Exception
     */
    public static function create(string $table, Closure|Blueprint $blueprint): void
    {
        if ($blueprint instanceof Closure) {
            $blueprint = new Blueprint($table, $blueprint);
        }

        $statement = 'CREATE TABLE (' . $blueprint->sew() . ');';
    }

    public static function update(): void
    {

    }

    public static function delete(): void
    {

    }
}
