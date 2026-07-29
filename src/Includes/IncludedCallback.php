<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @phpstan-type IncludedCallbackSchema (Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)
 */
class IncludedCallback implements IncludeInterface
{
    /**
     * @param IncludedCallbackSchema $callback
     */
    public function __construct(protected Closure $callback) {}

    public function __invoke(Builder $query, string $relation): void
    {
        $query->with([
            $relation => $this->callback,
        ]);
    }
}
