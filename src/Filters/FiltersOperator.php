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
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        if (is_array($value)) {
            $query->where(function ($query) use ($value, $property) {
                foreach($value as $item) {
                    $this->__invoke($query, $item, $property);
                }
            });

            return;
        }

        $query->where($query->qualifyColumn($property), $this->filterOperator->value, $value, $this->boolean);
    }
}
