<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;

class IncludedCallback implements IncludeInterface
{
    public function __construct(private \Closure $callback)
    {
    }

    public function __invoke(Builder $query, string $include)
    {
        return $query->with([$include => $this->callback]);
    }
}
