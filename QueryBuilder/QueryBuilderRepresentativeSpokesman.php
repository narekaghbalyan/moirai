<?php

namespace Moarai\QueryBuilder;

class QueryBuilderRepresentativeSpokesman extends QueryBuilder
{
    public function select(array|string ...$columns): self
    {
        $this->selectClauseBinder(false, $columns);

        return $this;
    }

    public function distinct(array|string ...$columns): self
    {
        $this->selectClauseBinder(true, $columns);

        return $this;
    }

    public function from(string $table): self
    {
        $this->fromClauseBinder($table);

        return $this;
    }

    public function table(string $table): self
    {
        $this->from($table);

        return $this;
    }
}