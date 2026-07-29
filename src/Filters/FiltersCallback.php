<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements Filter<TModel>
 */
class FiltersCallback implements Filter
{
    /** @var callable */
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
