<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @template TModel of Model
 *
 * @implements IncludeInterface<TModel>
 */
class IncludedCount implements IncludeInterface
{
    public function __construct(
        protected ?Closure $constraint = null,
    ) {}

    public function __invoke(Builder $query, string $count): void
    {
        $suffix = config('query-builder.suffixes.count', 'Count');
        $relation = Str::endsWith($count, $suffix) ? Str::beforeLast($count, $suffix) : $count;

        $query->withCount($this->constraint ? [$relation => $this->constraint] : $relation);
    }
}
