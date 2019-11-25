<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * FiltersTrashed provides filter for soft deleted (trashed) records.
 *
 * This filter responds to particular values:
 *
 * - 'with' - include 'trashed' records to the result set.
 * - 'only' - return only 'trashed' records at the result set.
 * - any other - return only records without 'trashed' at the result set.
 *
 * Usage example:
 *
 * ```php
 * QueryBuilder::for(Item::class)
 *     ->allowedFilters([
 *         AllowedFilter::trashed(),
 *         // ...
 *     ]);
 * ```
 *
 * @see \Illuminate\Database\Eloquent\SoftDeletes
 * @see \Spatie\QueryBuilder\AllowedFilter::trashed()
 */
class FiltersTrashed implements Filter
{
    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($value === 'with') {
            $query->withTrashed();

            return;
        }

        if ($value === 'only') {
            $query->onlyTrashed();

            return;
        }

        $query->withoutTrashed();
    }
}
