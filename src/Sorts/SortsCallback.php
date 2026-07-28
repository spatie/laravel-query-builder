<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements Sort<TModel>
 *
 * @phpstan-type SortsCallbackSchema callable(\Illuminate\Database\Eloquent\Builder<TModel>,bool,non-empty-string):void
 */
class SortsCallback implements Sort
{
    /** @var SortsCallbackSchema */
    private $callback;

    /**
     * @param SortsCallbackSchema $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        call_user_func($this->callback, $query, $descending, $property);
    }
}
