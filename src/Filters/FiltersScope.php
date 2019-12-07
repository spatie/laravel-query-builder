<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FiltersScope implements Filter
{
    public function __invoke(Builder $query, $values, string $property)
    {
        $scope = Str::camel($property);

        $values = Arr::wrap($values);

        $query->$scope(...$values);
    }
}
