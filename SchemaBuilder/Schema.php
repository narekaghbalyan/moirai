<?php

namespace Moarai\SchemaBuilder;

use Closure;

class Schema extends SchemaBuilder
{
    public static function create(string $table, Closure $callback)
    {
        dump($callback);
        dd($table);
    }
}