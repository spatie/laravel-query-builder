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

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements Filter<TModelClass>
 */
class FiltersScope implements Filter
{
    public function __invoke(Builder $query, mixed $values, string $property): void
    {
        $propertyParts = collect(explode('.', $property));

        $scope = Str::camel($propertyParts->pop());

        $values = array_values(Arr::wrap($values));
        $values = $this->resolveParameters($query, $values, $scope);

        $relation = $propertyParts->implode('.');

        if ($relation) {
            $query->whereHas($relation, function (Builder $query) use ($scope, $values) {
                $query->$scope(...$values);
            });

            return;
        }

        $query->$scope(...$values);
    }

    protected function resolveParameters(Builder $query, array $values, string $scope): array
    {
        if (! $query->getModel()->hasNamedScope($scope)) {
            return $values;
        }

        $reflectionObject = new ReflectionObject($query->getModel());
        $scopeMethod = method_exists($query->getModel(), 'scope'.ucfirst($scope))
            ? 'scope'.ucfirst($scope)
            : $scope;

        try {
            $parameters = $reflectionObject->getMethod($scopeMethod)
                ->getParameters();
        } catch (ReflectionException) {
            return $values;
        }

        foreach ($parameters as $parameter) {
            if (! $this->getClass($parameter)?->isSubclassOf(Model::class)) {
                continue;
            }

            /** @var TModelClass $model */
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
