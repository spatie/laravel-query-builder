<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionObject;

class FiltersScope implements Filter
{
    public function __invoke(Builder $query, $values, string $property): Builder
    {
        $scope = Str::camel($property);

        $values = Arr::wrap($values);
        $values = $this->resolveTypeHintedParameters($query, $values, $scope);

        return $query->$scope(...$values);
    }

    protected function resolveTypeHintedParameters(Builder $query, $values, string $scope): array
    {
        $scopeParameters = (new ReflectionObject($query->getModel()))
            ->getMethod('scope' . ucfirst($scope))
            ->getParameters();

        foreach ($scopeParameters as $scopeParameter) {
            if (! optional($scopeParameter->getClass())->isSubclassOf(Model::class)) {
                continue;
            }

            $model = $scopeParameter->getClass()->newInstance();
            $index = $scopeParameter->getPosition() - 1;
            $value = $values[$index];

            if (is_numeric($value)) {
                $values[$index] = $model::findOrFail($value);
            }
        }

        return $values;
    }
}
