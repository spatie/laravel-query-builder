<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface IncludeInterface
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<TModelClass> $query
     *
     * @return mixed
     */
    public function __invoke(Builder $query, string $include);
}
