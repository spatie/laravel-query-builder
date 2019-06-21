<?php

namespace Spatie\QueryBuilder\Sorts;

use Spatie\QueryBuilder\QueryBuilder;

class SortsField implements Sort
{
    public function __invoke(QueryBuilder $query, $descending, string $property)
    {
        $query->orderBy($property, $descending ? 'desc' : 'asc');
    }
}
