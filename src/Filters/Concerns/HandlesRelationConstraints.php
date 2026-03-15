<?php

namespace Spatie\QueryBuilder\Filters\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HandlesRelationConstraints
{
    protected array $relationConstraints = [];

    protected function isRelationProperty(Builder $query, string $property): bool
    {
        if (! Str::contains($property, '.')) {
            return false;
        }

        if (in_array($property, $this->relationConstraints)) {
            return false;
        }

        $firstRelationship = explode('.', $property)[0];

        if (! method_exists($query->getModel(), $firstRelationship)) {
            return false;
        }

        return is_a($query->getModel()->{$firstRelationship}(), Relation::class);
    }

    protected function withRelationConstraint(Builder $query, mixed $value, string $property): void
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(fn (Collection $parts) => [
                $parts->except(count($parts) - 1)->implode('.'),
                $parts->last(),
            ]);

        $query->whereHas($relation, function (Builder $query) use ($property, $value) {
            $this->relationConstraints[] = $property = $query->qualifyColumn($property);

            $this->__invoke($query, $value, $property);
        });
    }
}
