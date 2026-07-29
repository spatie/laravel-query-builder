<?php

namespace Spatie\QueryBuilder\Filters\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @template TModel of Model
 *
 * @phpstan-require-implements Filter<TModel>
 */
trait HandlesRelationConstraints
{
    /** @var array<int, string> */
    protected array $relationConstraints = [];

    /**
     * @param  Builder<TModel>  $query
     */
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

    /**
     * @param  Builder<TModel>  $query
     */
    protected function withRelationConstraint(Builder $query, mixed $value, string $property): void
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(fn (Collection $parts) => [
                $parts->except([$parts->count() - 1])->implode('.'),
                $parts->last(),
            ]);

        $query->whereHas($relation, function (Builder $relationQuery) use ($property, $value) {
            /** @var Builder<TModel> $relationQuery */
            $this->relationConstraints[] = $property = $relationQuery->qualifyColumn($property);

            $this->__invoke($relationQuery, $value, $property);
        });
    }
}
