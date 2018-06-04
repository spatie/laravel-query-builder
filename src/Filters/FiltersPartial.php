<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class FiltersPartial implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $sql = "LOWER({$property}) LIKE ?";

        if (is_array($value)) {
            return $query->where(function (Builder $query) use ($value, $sql) {
                foreach ($value as $partialValue) {
                    $partialValue = strtolower($partialValue);

                    $query->orWhereRaw($sql, ["%{$partialValue}%"]);
                }
            });
        }

        $value = strtolower($value);

        return $query->whereRaw($sql, ["%{$value}%"]);
    }
}
