<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
trait SortsQuery
{
    protected Collection $allowedSorts;

    /**
     * @param AllowedSort<TModel>|non-empty-string $sorts
     */
    public function allowedSorts(AllowedSort|string ...$sorts): static
    {
        $this->allowedSorts = collect($sorts)->map(function ($sort) {
            if ($sort instanceof AllowedSort) {
                return $sort;
            }

            return AllowedSort::field(ltrim($sort, '-'));
        });

        $this->ensureAllSortsExist();

        $this->addRequestedSortsToQuery();

        return $this;
    }

    /**
     * @param AllowedSort<TModel>|non-empty-string $sorts
     */
    public function defaultSort(AllowedSort|string ...$sorts): static
    {
        return $this->defaultSorts(...$sorts);
    }

    /**
     * @param AllowedSort<TModel>|non-empty-string $sorts
     */
    public function defaultSorts(AllowedSort|string ...$sorts): static
    {
        if ($this->request->sorts()->isNotEmpty()) {
            return $this;
        }

        collect($sorts)
            ->map(function ($sort) {
                if ($sort instanceof AllowedSort) {
                    return $sort;
                }

                return AllowedSort::field($sort);
            })
            ->each(fn (AllowedSort $sort) => $sort->sort($this));

        return $this;
    }

    protected function addRequestedSortsToQuery(): void
    {
        $this->request->sorts()
            ->each(function (string $property) {
                $descending = $property[0] === '-';

                $key = ltrim($property, '-');

                $sort = $this->findSort($key);

                $sort?->sort($this, $descending);
            });
    }

    /**
     * @return ?AllowedSort<TModel>
     */
    protected function findSort(string $property): ?AllowedSort
    {
        return $this->allowedSorts
            ->first(fn (AllowedSort $sort) => $sort->isSort($property));
    }

    protected function ensureAllSortsExist(): void
    {
        $shouldSkip = config()->boolean('query-builder.disable_invalid_sort_query_exception', false);
        if ($shouldSkip) {
            return;
        }

        $requestedSortNames = $this->request->sorts()->map(fn (string $sort) => ltrim($sort, '-'));

        $allowedSortNames = $this->allowedSorts->map(fn (AllowedSort $sort) => $sort->getName());

        $unknownSorts = $requestedSortNames->diff($allowedSortNames);

        if ($unknownSorts->isNotEmpty()) {
            throw InvalidSortQuery::sortsNotAllowed($unknownSorts, $allowedSortNames);
        }
    }
}
