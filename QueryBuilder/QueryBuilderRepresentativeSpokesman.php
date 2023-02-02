<?php

namespace Moarai\QueryBuilder;

class QueryBuilderRepresentativeSpokesman extends QueryBuilder
{
    public function select(array|string ...$columns): self
    {
        $this->selectClauseBinder(false, $columns);

        return $this;
    }
}