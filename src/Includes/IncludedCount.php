<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

class IncludedCount implements Includable
{
    public function __invoke(QueryBuilder $query, string $count)
    {
        $query->withCount(Str::before($count, config('query-builder.count_suffix')));
    }
}
