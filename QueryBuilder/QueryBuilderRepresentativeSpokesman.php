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

    public function where(string|array|callable $column, string|null $operator = null, string|null $value = ''): self
    {
        $this->baseConditionClauseBinder('', 'where', $column, $operator, $value);

        return $this;
    }

    public function whereBetween(string|callable $column, array|string|int|float $range = '', string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange);

        return $this;
    }

    public function whereBetweenColumns(string|callable $column, array|string|int|float $range = '', string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange, false, true);

        return $this;
    }

    public function whereIn(string|callable $column, array $setOfSupposedVariables = []): self
    {
        $this->whereInClauseBinder('', $column, $setOfSupposedVariables);

        return $this;
    }

    public function whereNull(string|callable $column): self
    {
        $this->whereNullClauseBinder('', $column, false);

        return $this;
    }

    public function whereExists(callable $callback): self
    {
        $this->whereExistsClauseBinder('', $callback);

        return $this;
    }

    public function whereColumn(string|array $firstColumn, string|null $operator = null, string|null $secondColumn = null): self
    {
        $this->whereColumnClauseBinder('', $firstColumn, $operator, $secondColumn);

        return $this;
    }

    public function whereFullText(string $column, string $value): self
    {
        $this->whereFullTextClauseBinder('', $column, $value, false);

        return $this;
    }

    public function orderBy(string|array $column, string $direction = 'asc'): self
    {
        $this->orderByClauseBinder($column, $direction);

        return $this;
    }

    public function latest(string|array $column): self
    {
        $this->orderBy($column, 'desc');

        return $this;
    }

    public function oldest(string|array $column): self
    {
        $this->orderBy($column);

        return $this;
    }

    public function inRandomOrder(string $column): self
    {
        $this->orderByClauseBinder($column, '', true);

        return $this;
    }

    public function groupBy(string|array ...$columns): self
    {
        $this->groupByClauseBinder($columns);

        return $this;
    }

    public function having(string|array $column, string|null $operator = null, string|null $value = ''): self
    {
        $this->baseConditionClauseBinder('', 'having', $column, $operator, $value);

        return $this;
    }

    // TODO having between

    public function limit(int $count): self
    {
        $this->limitClauseBinder($count);

        return $this;
    }

    public function offset(int $count): self
    {
        $this->offsetClauseBinder($count);

        return $this;
    }

    public function skip(int $count): self
    {
        $this->offset($count);

        return $this;
    }

    public function take(int $count): self
    {
        $this->limit($count);

        return $this;
    }
}