<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<TModelClass> $query
     * @param mixed $value
     * @param string $property
     *
     * @return mixed
     */
    public function __invoke(Builder $query, $value, string $property);
}
