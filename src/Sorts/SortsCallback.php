<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;

class SortsCallback implements Sort
{
    /** @var callable */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        call_user_func($this->callback, $query, $descending, $property);
    }
}
