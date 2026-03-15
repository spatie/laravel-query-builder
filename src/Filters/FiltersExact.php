<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Concerns\HandlesRelationConstraints;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements Filter<TModelClass>
 */
class FiltersExact implements Filter
{
    use HandlesRelationConstraints;

    public function __construct(protected bool $addRelationConstraint = true)
    {
    }

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if ($this->addRelationConstraint && $this->isRelationProperty($query, $property)) {
            $this->withRelationConstraint($query, $value, $property);

            return;
        }

        if (is_array($value)) {
            $query->whereIn($query->qualifyColumn($property), $value);

            return;
        }

        $query->where($query->qualifyColumn($property), '=', $value);
    }
}
