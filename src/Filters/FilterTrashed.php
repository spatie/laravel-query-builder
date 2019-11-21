<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * FilterTrashed provides filter for soft deleted (trashed) records.
 *
 * This filter responds to particular values:
 *
 * - 'with' - include 'trashed' records to the result set.
 * - 'only' - return only 'trashed' records at the result set.
 * - any other - return only records without 'trashed' at the result set.
 *
 * @see \Illuminate\Database\Eloquent\SoftDeletes
 * @see \Spatie\QueryBuilder\AllowedFilter::trashed()
 */
class FilterTrashed implements Filter
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
