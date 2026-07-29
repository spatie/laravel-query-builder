<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\Filters\Concerns\HandlesRelationConstraints;

/**
 * @template TModel of Model
 *
 * @implements Filter<TModel>
 */
class FiltersExact implements Filter
{
    /** @use HandlesRelationConstraints<TModel> */
    use HandlesRelationConstraints;

    public function __construct(protected bool $addRelationConstraint = true) {}

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
