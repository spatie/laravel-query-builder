<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class IncludedCallback implements IncludeInterface
{
    protected Closure $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Builder $query, string $relation)
    {
        $query->with([
            $relation => $this->callback,
        ]);
    }
}
