<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\Exceptions\WildcardNotAllowedInEnvironment;

trait SortsQuery
{
    protected Collection $allowedSorts;

    public function allowedSorts(AllowedSort|string ...$sorts): static
    {
        if (count($sorts) === 1 && $sorts[0] === '*') {
            if (! app()->environment('local', 'testing')) {
                throw WildcardNotAllowedInEnvironment::create(app()->environment());
            }

            $this->allowedSorts = $this->request->sorts()->map(
                fn (string $sort) => AllowedSort::field(ltrim($sort, '-'))
            );

            $this->addRequestedSortsToQuery();

            return $this;
        }

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

    public function defaultSort(AllowedSort|string ...$sorts): static
    {
        return $this->defaultSorts(...$sorts);
    }

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

    protected function findSort(string $property): ?AllowedSort
    {
        return $this->allowedSorts
            ->first(fn (AllowedSort $sort) => $sort->isSort($property));
    }

    protected function ensureAllSortsExist(): void
    {
        if (config('query-builder.disable_invalid_sort_query_exception', false)) {
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
