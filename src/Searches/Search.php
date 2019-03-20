<?php

namespace Spatie\QueryBuilder\Searches;

use Illuminate\Database\Eloquent\Builder;

interface Search
{
    public function __invoke(Builder $query, $value, string $property, ?string $modifier = null) : Builder;
}
