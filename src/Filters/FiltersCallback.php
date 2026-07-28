<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements Filter<TModel>
 *
 * @phpstan-type FiltersCallbackSchema callable(\Illuminate\Database\Eloquent\Builder<TModel>,mixed,non-empty-string): void
 */
class FiltersCallback implements Filter
{
    /** @var FiltersCallbackSchema */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        call_user_func($this->callback, $query, $value, $property);
    }
}
