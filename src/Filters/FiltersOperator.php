<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\Filters\Concerns\HandlesRelationConstraints;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements Filter<TModelClass>
 */
class FiltersOperator implements Filter
{
    use HandlesRelationConstraints;

    public function __construct(
        protected bool $addRelationConstraint,
        protected FilterOperator $filterOperator,
        protected string $boolean,
    ) {
    }

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $filterOperator = $this->filterOperator;

        if ($this->addRelationConstraint && $this->isRelationProperty($query, $property)) {
            $this->withRelationConstraint($query, $value, $property);

            return;
        }

        if (is_array($value)) {
            $query->where(function (Builder $query) use ($value, $property) {
                foreach ($value as $item) {
                    $this->__invoke($query, $item, $property);
                }
            });

            return;
        } elseif ($this->filterOperator->isDynamic() && $value !== null) {
            $filterOperator = $this->getDynamicFilterOperator($value);
            $this->removeDynamicFilterOperatorFromValue($value, $filterOperator);
            if ($value === '') {
                $value = null;
            }
        } elseif ($this->filterOperator->isDynamic() && $value === null) {
            $filterOperator = FilterOperator::EQUAL;
        }

        $query->where($query->qualifyColumn($property), $filterOperator->value, $value, $this->boolean);
    }

    protected function getDynamicFilterOperator(string $value): FilterOperator
    {
        $filterOperator = FilterOperator::EQUAL;

        foreach (FilterOperator::cases() as $filterOperatorCase) {
            if (str_starts_with($value, $filterOperatorCase->value) && ! $filterOperatorCase->isDynamic()) {
                $filterOperator = $filterOperatorCase;
            }
        }

        return $filterOperator;
    }

    protected function removeDynamicFilterOperatorFromValue(string &$value, FilterOperator $filterOperator): void
    {
        if (str_contains($value, $filterOperator->value)) {
            $value = substr_replace($value, '', 0, strlen($filterOperator->value));
        }
    }
}
