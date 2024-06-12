<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class IncludedExists implements IncludeInterface
{
    public function __invoke(Builder $query, string $exists)
    {
        $exists = Str::before($exists, config('query-builder.exists_suffix', 'Exists'));

        $query
            ->withExists($exists)
            ->withCasts([
                "{$exists}_exists" => 'boolean',
            ]);
    }
}
