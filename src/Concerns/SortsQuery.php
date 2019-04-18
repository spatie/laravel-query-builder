<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\Sort;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\ColumnNameSanitizer;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

trait SortsQuery
{
    /** @var \Illuminate\Support\Collection */
    private $defaultSorts;

    /** @var \Illuminate\Support\Collection */
    private $allowedSorts;

    public function allowedSorts($sorts): self
    {
        $sorts = is_array($sorts) ? $sorts : func_get_args();

        if (! $this->request->sorts()) {
            return $this;
        }

        $this->allowedSorts = collect($sorts)->map(function ($sort) {
            if ($sort instanceof Sort) {
                return $sort;
            }

            return Sort::field(ltrim($sort, '-'));
        });

        $this->guardAgainstUnknownSorts();

        $this->parseRequestedSorts();

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
        $sorts = is_array($sorts) ? $sorts : func_get_args();

        $this->defaultSorts = collect($sorts)->map(function ($sort) {
            if (is_string($sort)) {
                return Sort::field($sort);
            }

            return $sort;
        });

        $this->parseRequestedSorts();

        return $this;
    }

    private function parseRequestedSorts()
    {
        $sorts = $this->request->sorts();

        if ($sorts->isEmpty()) {
            $this->addDefaultSortsToQuery();

            return;
        }

        $this
            ->filterDuplicates($sorts)
            ->each(function (string $property) {
                $descending = $property[0] === '-';

                $key = ltrim($property, '-');

                $sort = $this->findSort($key);

                $sort->sort($this, $descending);
            });
    }

    protected function findSort(string $property): ?Sort
    {
        return $this->allowedSorts
            ->merge($this->defaultSorts)
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

    private function filterDuplicates(Collection $sorts): Collection
    {
        if (! is_array($orders = $this->getQuery()->orders)) {
            return $sorts;
        }

        return $sorts->reject(function (string $sort) use ($orders) {
            $toSort = [
                'column' => ltrim($sort, '-'),
                'direction' => ($sort[0] === '-') ? 'desc' : 'asc',
            ];
            foreach ($orders as $order) {
                if ($order === $toSort) {
                    return true;
                }
            }
        });
    }

    private function addDefaultSortsToQuery(): void
    {
        optional($this->defaultSorts)->each(function (Sort $sort) {
            $sort->sort($this);
        });
    }
}
