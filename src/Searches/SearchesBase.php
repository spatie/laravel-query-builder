<?php

namespace Spatie\QueryBuilder\Searches;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

abstract class SearchesBase implements Search
{
    protected $relationConstraints = [];

    abstract public function __invoke(Builder $query, $value, string $property, ?string $modifier = null) : Builder;

    protected function isRelationProperty(Builder $query, string $property) : bool
    {
        if (! Str::contains($property, '.')) {
            return false;
        }

        if (in_array($property, $this->relationConstraints)) {
            return false;
        }

        if (Str::startsWith($property, $query->getModel()->getTable().'.')) {
            return false;
        }

        return true;
    }

    protected function withRelationConstraint(Builder $query, $value, string $property) : Builder
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(function (Collection $parts) {
                return [
                    $parts->except(count($parts) - 1)->map([Str::class, 'camel'])->implode('.'),
                    $parts->last(),
                ];
            });

        return $query->whereHas($relation, function (Builder $query) use ($value, $relation, $property) {
            $this->relationConstraints[] = $property = $query->getModel()->getTable().'.'.$property;

            $this->__invoke($query, $value, $property);
        });
    }

    protected function encloseValue($value)
    {
        return "{$value}";
    }
}
