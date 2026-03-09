<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface Filter
{
    /**
     * @param Builder<TModelClass> $query
     */
    public function __invoke(Builder $query, mixed $value, string $property): void;
}
