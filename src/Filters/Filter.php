<?php

namespace Spatie\QueryBuilder\Filters;

use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    public function __invoke(QueryBuilder $query, $value, string $property);
}
