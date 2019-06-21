<?php

namespace Spatie\QueryBuilder\Sorts;

use Spatie\QueryBuilder\QueryBuilder;

interface Sort
{
    public function __invoke(QueryBuilder $query, $descending, string $property);
}
