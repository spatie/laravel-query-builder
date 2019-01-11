<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;

class SortsField implements Sort
{
    public function __invoke(Builder $query, $descending, string $property) : Builder
    {
        return $query->orderBy($property, $descending ? 'desc' : 'asc');
    }
}
