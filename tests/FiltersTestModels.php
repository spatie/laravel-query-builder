<?php

namespace Spatie\QueryBuilder\Tests;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersTestModels implements Filter
{
    public function __invoke(Builder $query, $value, string $property) : Builder
    {
        return $query->where($property, $value);
    }
}
