<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface Sort
{
    /**
     * @param  Builder<TModel>  $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void;
}
