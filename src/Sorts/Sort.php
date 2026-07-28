<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface Sort
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<TModel> $query
     * @param non-empty-string $property
     */
    public function __invoke(Builder $query, bool $descending, string $property): void;
}
