<?php

namespace Moirai\DDL;

use Closure;

class Schema extends SchemaBuilder
{
    public static function create(string $table, Closure $callback)
    {
        $blueprint = new Blueprint($table, $callback);

//        $ddlExpression = 'CREATE TABLE (' . $blueprint . ')';
//
//        dd($ddlExpression);
    }
}