<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

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
