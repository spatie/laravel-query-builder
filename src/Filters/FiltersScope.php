<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class FiltersScope implements Filter
{
    public function __invoke(Builder $query, $values, string $property)
    {
        $scope = Str::camel($property);

        $values = Arr::wrap($values);

        $query->$scope(...$values);
    }
}
