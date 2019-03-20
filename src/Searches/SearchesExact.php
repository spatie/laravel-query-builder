<?php

namespace Spatie\QueryBuilder\Searches;

use Illuminate\Database\Eloquent\Builder;

class SearchesExact extends SearchesBase
{
    public function __invoke(Builder $query, $value, string $property, ?string $modifier = null) : Builder
    {
        if ($this->isRelationProperty($query, $property)) {
            return $this->withRelationConstraint($query, $value, $property);
        }

        if (is_array($value)) {
            return $query->orWhereIn($property, $value);
        }

        return $query->orWhere($property, '=', $value);
    }
}
