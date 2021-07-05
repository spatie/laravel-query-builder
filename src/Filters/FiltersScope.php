<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionParameter;
use ReflectionUnionType;
use Spatie\QueryBuilder\Exceptions\InvalidFilterValue;

class FiltersScope implements Filter
{
    public function __invoke(Builder $query, $values, string $property): Builder
    {
        $propertyParts = collect(explode('.', $property));

        $scope = Str::camel($propertyParts->pop()); // TODO: Make this configurable?

        $values = array_values(Arr::wrap($values));
        $values = $this->resolveParameters($query, $values, $scope);

        $relation = $propertyParts->implode('.');

        if ($relation) {
            return $query->whereHas($relation, function (Builder $query) use (
                $scope,
                $values
            ) {
                return $query->$scope(...$values);
            });
        }

        return $query->$scope(...$values);
    }

    protected function resolveParameters(Builder $query, $values, string $scope): array
    {
        try {
            $parameters = (new ReflectionObject($query->getModel()))
                ->getMethod('scope' . ucfirst($scope))
                ->getParameters();
        } catch (ReflectionException $e) {
            return $values;
        }

        foreach ($parameters as $parameter) {
            if (! optional($this->getClass($parameter))->isSubclassOf(Model::class)) {
                continue;
            }

            $model = $this->getClass($parameter)->newInstance();
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

    protected function getClass(ReflectionParameter $parameter): ?ReflectionClass
    {
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            return $parameter->getClass();
        }

        $type = $parameter->getType();

        if (is_null($type)) {
            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        if ($type->getName() === 'self') {
            return $parameter->getDeclaringClass();
        }

        return new ReflectionClass($type->getName());
    }
}
