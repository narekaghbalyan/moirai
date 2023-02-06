<?php

namespace Moarai\QueryBuilder;

class FullTextSearchModifiers
{
    const NATURAL_LANGUAGE_MODE_WITH_QUERY_EXPANSION = 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION';

    const NATURAL_LANGUAGE_MODE = 'IN NATURAL LANGUAGE MODE';

    const WITH_QUERY_EXPANSION = 'WITH QUERY EXPANSION';

    const BOOLEAN_MODE = 'IN BOOLEAN MODE';

    public static function getAllDrivers(): array
    {
        $reflectionClass = new ReflectionClass(__CLASS__);

        return $reflectionClass->getConstants();
    }
}