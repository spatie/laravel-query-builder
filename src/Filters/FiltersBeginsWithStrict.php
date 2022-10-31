<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FiltersBeginsWithStrict extends FiltersPartial implements Filter
{
    protected function applyWhere(Builder $query, $value, string $property)
    {
        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));

        $query->whereRaw("{$wrappedProperty} LIKE ?", ["{$value}%"]);
    }
}
