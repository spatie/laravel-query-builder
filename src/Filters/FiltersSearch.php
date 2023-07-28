<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FiltersSearch implements Filter
{
    /** {@inheritdoc} */
    public function __invoke(Builder $query, $values, string $property)
    {
        return $query->where(function ($q) use ($values){
            foreach($values as $item):
                $q->orWhere($item['column'], 'LIKE', '%' . $item['value'] . '%');
            endforeach;
        });
    }
}
