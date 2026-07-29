<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface Filter
{
    /**
     * @param  Builder<TModel>  $query
     */
    public function __invoke(Builder $query, mixed $value, string $property): void;
}
