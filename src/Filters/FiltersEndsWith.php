<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends FiltersPartial<TModel>
 */
class FiltersEndsWith extends FiltersPartial
{
    /**
     * @return array{string, array<int, string>}
     */
    protected function getWhereRawParameters(mixed $value, string $property, string $driver): array
    {
        return [
            "{$property} LIKE ?".static::maybeSpecifyEscapeChar($driver),
            ['%'.static::escapeLike($value)],
        ];
    }
}
