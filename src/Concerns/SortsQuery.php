<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\Sort;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

trait SortsQuery
{
    /** @var \Illuminate\Support\Collection */
    private $allowedSorts;

    public function allowedSorts($sorts): self
    {
        if ($this->request->sorts()->isEmpty()) {
            // We haven't got any requested sorts. No need to parse allowed sorts.

            return $this;
        }

        $sorts = is_array($sorts) ? $sorts : func_get_args();

        $this->allowedSorts = collect($sorts)->map(function ($sort) {
            if ($sort instanceof Sort) {
                return $sort;
            }

            return Sort::field(ltrim($sort, '-'));
        });

        $this->guardAgainstUnknownSorts();

        $this->addRequestedSortsToQuery(); // allowed is known & request is known, add what we can, if there is no request, -wait

        return $this;
    }

    /**
     * @param array|string|\Spatie\QueryBuilder\Sort $sorts
     *
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    public function defaultSort($sorts): self
    {
        return $this->defaultSorts(func_get_args());
    }

    /**
     * @param array|string|\Spatie\QueryBuilder\Sort $sorts
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
                if ($sort instanceof Sort) {
                    return $sort;
                }

                return Sort::field($sort);
            })
            ->each(function (Sort $sort) {
                $sort->sort($this);
            });

        return $this;
    }

    private function addRequestedSortsToQuery()
    {
        $this->request->sorts()
            ->each(function (string $property) {
                $descending = $property[0] === '-';

                $key = ltrim($property, '-');

                $sort = $this->findSort($key);

                $sort->sort($this, $descending);
            });
    }

    private function findSort(string $property): ?Sort
    {
        return $this->allowedSorts
            ->first(function (Sort $sort) use ($property) {
                return $sort->isForProperty($property);
            });
    }

    private function guardAgainstUnknownSorts(): void
    {
        $requestedSortNames = $this->request->sorts()->map(function (string $sort) {
            return ltrim($sort, '-');
        });

        $allowedSortNames = $this->allowedSorts->map(function (Sort $sort) {
            return $sort->getProperty();
        });

        $unknownSorts = $requestedSortNames->diff($allowedSortNames);

        if ($unknownSorts->isNotEmpty()) {
            throw InvalidSortQuery::sortsNotAllowed($unknownSorts, $allowedSortNames);
        }
    }
}
