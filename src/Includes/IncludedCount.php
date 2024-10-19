<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class IncludedCount implements IncludeInterface
{
    public function __invoke(Builder $query, string $count)
    {
        $suffix = config('query-builder.count_suffix', 'Count');
        $relation = Str::endsWith($count, $suffix) ? Str::beforeLast($count, $suffix) : $count;

        $query->withCount($relation);
    }
}
