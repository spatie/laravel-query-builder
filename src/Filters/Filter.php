<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

interface Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model> $query
     * @param mixed $value
     * @param string $property
     *
     * @return mixed
     */
    public function __invoke(Builder $query, $value, string $property);
}
