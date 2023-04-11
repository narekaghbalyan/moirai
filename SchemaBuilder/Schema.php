<?php

namespace Moarai\SchemaBuilder;

use Closure;

class Schema extends SchemaBuilder
{
    public static function create(string $table, Closure $callback)
    {
        $blueprint = new Blueprint($table, $callback);

        dd($blueprint);
    }
}