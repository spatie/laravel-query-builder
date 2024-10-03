<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Enums\FilterOperator;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FiltersOperator extends FiltersExact implements Filter
{
    public function __construct(protected bool $addRelationConstraint, protected FilterOperator $filterOperator, protected string $boolean)
    {
    }

    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        $filterOperator = $this->filterOperator;

        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        if (is_array($value)) {
            $query->where(function ($query) use ($value, $property) {
                foreach ($value as $item) {
                    $this->__invoke($query, $item, $property);
                }
            });

            return;
        } elseif ($this->filterOperator->isDynamic()) {
            $filterOperator = $this->getDynamicFilterOperator($value);
            $this->removeDynamicFilterOperatorFromValue($value, $filterOperator);
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

    protected function removeDynamicFilterOperatorFromValue(string &$value, FilterOperator $filterOperator)
    {
        if (str_contains($value, $filterOperator->value)) {
            $value = substr_replace($value, '', 0, strlen($filterOperator->value));
        }
    }
}
