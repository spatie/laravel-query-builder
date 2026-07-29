<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements Filter<TModel>
 */
class FiltersTrashed implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if ($value === 'with') {
            $query->withTrashed(); // @phpstan-ignore-line

            return;
        }

        if ($value === 'only') {
            $query->onlyTrashed(); // @phpstan-ignore-line

            return;
        }

        $query->withoutTrashed(); // @phpstan-ignore-line
    }
}
