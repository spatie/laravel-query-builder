<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class IncludedCount implements IncludeInterface
{
    public function __invoke(Builder $query, string $count)
    {
        $query->withCount(Str::before($count, config('query-builder.count_suffix')));
    }
}
