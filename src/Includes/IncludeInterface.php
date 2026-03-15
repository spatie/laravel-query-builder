<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface IncludeInterface
{
    /**
     * @param Builder<TModelClass> $query
     */
    public function __invoke(Builder $query, string $include): void;
}
