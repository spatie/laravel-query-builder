<?php

namespace Spatie\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;

class SortsCallback implements Sort
{
    /**
     * @var callable a PHP callback of the following signature:
     * `function (\Illuminate\Database\Eloquent\Builder $builder, bool $descending, string $property)`
     */
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /** {@inheritdoc} */
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        return call_user_func($this->callback, $query, $descending, $property);
    }
}
