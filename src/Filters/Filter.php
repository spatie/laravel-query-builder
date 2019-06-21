<?php

namespace Spatie\QueryBuilder\Filters;

use Spatie\QueryBuilder\QueryBuilder;

interface Filter
{
    public function __invoke(QueryBuilder $query, $value, string $property);
}
