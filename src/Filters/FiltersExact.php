<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FiltersExact implements Filter
{
    protected $relationConstraints = [];

    /** @var bool */
    protected $addRelationConstraint = true;

    public function __construct(bool $addRelationConstraint = true)
    {
        $this->addRelationConstraint = $addRelationConstraint;
    }

    public function __invoke(Builder $query, $value, string $property)
    {
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        if (is_array($value)) {
            $query->whereIn($property, $value);

            return;
        }

        $query->where($property, '=', $value);
    }

    protected function isRelationProperty(Builder $query, string $property): bool
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

    protected function withRelationConstraint(Builder $query, $value, string $property)
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(function (Collection $parts) {
                return [
                    $parts->except(count($parts) - 1)->map([Str::class, 'camel'])->implode('.'),
                    $parts->last(),
                ];
            });

        $query->whereHas($relation, function (Builder $query) use ($value, $relation, $property) {
            $this->relationConstraints[] = $property = $query->getModel()->getTable().'.'.$property;

            $this->__invoke($query, $value, $property);
        });
    }
}
