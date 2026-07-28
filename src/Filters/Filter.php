<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface Filter
{
    /**
     * @param  Builder<TModel>  $query
     */
    public function __invoke(Builder $query, mixed $value, string $property): void;
}
