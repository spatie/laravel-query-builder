<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class FiltersScope implements Filter
{
    public function __invoke(Builder $query, $values, string $property) : Builder
    {
        $scope = camel_case($property);
        $values = array_wrap($values);

        return $query->$scope(...$values);
    }
}
