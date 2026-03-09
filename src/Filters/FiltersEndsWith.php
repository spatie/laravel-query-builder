<?php

namespace Spatie\QueryBuilder\Filters;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements Filter<TModelClass>
 */
class FiltersEndsWith extends FiltersPartial implements Filter
{
    protected function getWhereRawParameters(mixed $value, string $property, string $driver): array
    {
        return [
            "{$property} LIKE ?".static::maybeSpecifyEscapeChar($driver),
            ['%'.static::escapeLike($value)],
        ];
    }
}
