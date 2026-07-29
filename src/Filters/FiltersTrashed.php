<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements Filter<TModel>
 */
class FiltersTrashed implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        // The trashed methods only exist on builders for models that use the SoftDeletes trait,
        // which cannot be expressed in the Filter<TModel> contract.
        if ($value === 'with') {
            $query->withTrashed(); // @phpstan-ignore method.notFound

            return;
        }

        if ($value === 'only') {
            $query->onlyTrashed(); // @phpstan-ignore method.notFound

            return;
        }

        $query->withoutTrashed(); // @phpstan-ignore method.notFound
    }
}
