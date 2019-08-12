<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;

class SortsField implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $query->orderBy($property, $descending ? 'desc' : 'asc');
    }
}
