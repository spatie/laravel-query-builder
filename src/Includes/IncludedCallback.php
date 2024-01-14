<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class IncludedCallback implements IncludeInterface
{
    public function __construct(protected Closure $callback)
    {
    }

    public function __invoke(Builder $query, string $relation)
    {
        $query->with([
            $relation => $this->callback,
        ]);
    }
}
