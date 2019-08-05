<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

trait SortsQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedSorts;

    public function allowedSorts($sorts): self
    {
        if ($this->request->sorts()->isEmpty()) {
            // We haven't got any requested sorts. No need to parse allowed sorts.

            return $this;
        }

        $sorts = is_array($sorts) ? $sorts : func_get_args();

        $this->allowedSorts = collect($sorts)->map(function ($sort) {
            if ($sort instanceof AllowedSort) {
                return $sort;
            }

            return AllowedSort::field(ltrim($sort, '-'));
        });

        $this->ensureAllSortsExist();

        $this->addRequestedSortsToQuery(); // allowed is known & request is known, add what we can, if there is no request, -wait

        return $this;
    }

    /**
     * @param array|string|\Spatie\QueryBuilder\AllowedSort $sorts
     *
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    public function defaultSort($sorts): self
    {
        return $this->defaultSorts(func_get_args());
    }

    /**
     * @param array|string|\Spatie\QueryBuilder\AllowedSort $sorts
     *
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    public function defaultSorts($sorts): self
    {
        if ($this->request->sorts()->isNotEmpty()) {
            // We've got requested sorts. No need to parse defaults.

            return $this;
        }

        $sorts = is_array($sorts) ? $sorts : func_get_args();

        collect($sorts)
            ->map(function ($sort) {
                if ($sort instanceof AllowedSort) {
                    return $sort;
                }

                return AllowedSort::field($sort);
            })
            ->each(function (AllowedSort $sort) {
                $sort->sort($this);
            });

        return $this;
    }

    protected function addRequestedSortsToQuery()
    {
        $this->request->sorts()
            ->each(function (string $property) {
                $descending = $property[0] === '-';

                $key = ltrim($property, '-');

                $sort = $this->findSort($key);

                $sort->sort($this, $descending);
            });
    }

    protected function findSort(string $property): ?AllowedSort
    {
        return $this->allowedSorts
            ->first(function (AllowedSort $sort) use ($property) {
                return $sort->isSort($property);
            });
    }

    protected function ensureAllSortsExist(): void
    {
        $requestedSortNames = $this->request->sorts()->map(function (string $sort) {
            return ltrim($sort, '-');
        });

        $allowedSortNames = $this->allowedSorts->map(function (AllowedSort $sort) {
            return $sort->getName();
        });

        $unknownSorts = $requestedSortNames->diff($allowedSortNames);

        if ($unknownSorts->isNotEmpty()) {
            throw InvalidSortQuery::sortsNotAllowed($unknownSorts, $allowedSortNames);
        }
    }
}
