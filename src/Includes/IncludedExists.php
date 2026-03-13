<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class IncludedExists implements IncludeInterface
{
    public function __construct(
        protected ?Closure $constraint = null,
    ) {
    }

    public function __invoke(Builder $query, string $exists): void
    {
        $exists = Str::before($exists, config('query-builder.suffixes.exists', 'Exists'));

        $query
            ->withExists($this->constraint ? [$exists => $this->constraint] : $exists)
            ->withCasts([
                "{$exists}_exists" => 'boolean',
            ]);
    }
}
