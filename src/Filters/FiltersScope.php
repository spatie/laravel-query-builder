<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionObject;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValue;

class FiltersScope implements Filter
{
    public function __invoke(Builder $query, $values, string $property): Builder
    {
        $scope = Str::camel($property);

        $values = array_values(Arr::wrap($values));
        $values = $this->resolveParameters($query, $values, $scope);

        return $query->$scope(...$values);
    }

    protected function resolveParameters(Builder $query, $values, string $scope): array
    {
        try {
            $parameters = (new ReflectionObject($query->getModel()))
                ->getMethod('scope'.ucfirst($scope))
                ->getParameters();
        } catch (ReflectionException $e) {
            return $values;
        }

        foreach ($parameters as $parameter) {
            if (! optional($parameter->getClass())->isSubclassOf(Model::class)) {
                continue;
            }

            $model = $parameter->getClass()->newInstance();
            $index = $parameter->getPosition() - 1;
            $value = $values[$index];

            $result = $model->resolveRouteBinding($value);

            if ($result === null) {
                throw InvalidFilterValue::make($value);
            }

            $values[$index] = $result;
        }

        return $values;
    }
}
