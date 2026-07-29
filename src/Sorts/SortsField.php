<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements Sort<TModel>
 */
class SortsField implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy($property, $descending ? 'desc' : 'asc');
    }
}
