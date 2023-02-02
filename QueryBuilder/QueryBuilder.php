<?php

namespace Moarai\QueryBuilder;

class QueryBuilder
{
    use ClauseBindersToolkit;

    protected array $bindings = [
        'select' => [],
        'from' => [],
        'join' => [],
        'where' => [],
        'groupBy' => [],
        'having' => [],
        'orderBy' => [],
        'union' => [],
        'unionOrder' => [],
        'limit' => [],
        'offset' => []
    ];

    protected function selectClauseBinder(bool $distinct = false, array|string ...$columns): void
    {
        $flattenedColumns = $this->concludeGraveAccent($columns);

        if (empty($flattenedColumns)) {
            $flattenedColumns = '*';
        }

        $this->bind('select', [
            $distinct ? 'distinct' : '',
            $flattenedColumns
        ]);
    }

    protected function fromClauseBinder(string $table): void
    {
        $this->bind('from', [$this->concludeGraveAccent($table)]);
    }
}