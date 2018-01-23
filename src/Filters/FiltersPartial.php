<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class FiltersPartial implements Filter
{
    public function __invoke(Builder $query, $value, string $property) : Builder
    {
        if (is_array($value)) {
            return $query->where(function (Builder $query) use ($value, $property) {
                foreach ($value as $partialValue) {
                    $query->orWhere($property, 'LIKE', "%{$partialValue}%");
                }
            });
        }

        return $query->where($property, 'LIKE', "%{$value}%");
    }
}
