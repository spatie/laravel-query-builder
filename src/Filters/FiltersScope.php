<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

class FiltersScope implements Filter
{
    public function __invoke(QueryBuilder $query, $values, string $property)
    {
        $scope = Str::camel($property);

        $values = Arr::wrap($values);

        $query->$scope(...$values);
    }
}
