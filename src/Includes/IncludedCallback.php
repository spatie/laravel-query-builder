<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements IncludeInterface<TModel>
 */
class IncludedCallback implements IncludeInterface
{
    public function __construct(protected Closure $callback) {}

    public function __invoke(Builder $query, string $relation): void
    {
        $query->with([
            $relation => $this->callback,
        ]);
    }
}
