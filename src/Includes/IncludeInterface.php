<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface IncludeInterface
{
    /**
     * @param  Builder<TModel>  $query
     */
    public function __invoke(Builder $query, string $include): void;
}
