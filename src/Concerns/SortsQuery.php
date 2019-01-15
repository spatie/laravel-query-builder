<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\Sort;

trait SortsQuery
{
    /** @var \Spatie\QueryBuilder\Sort|null */
    protected $defaultSort;

    /** @var \Illuminate\Support\Collection */
    protected $allowedSorts;

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

        $this->parseSorts();

        return $this;
    }

    /**
     * @param string|\Spatie\QueryBuilder\Sort $sort
     *
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    public function defaultSort($sort): self
    {
        if (is_string($sort)) {
            $sort = Sort::field($sort);
        }

        $this->defaultSort = $sort;

        $this->parseSorts();

        return $this;
    }

    protected function parseSorts()
    {
        $sorts = $this->request->sorts();

        if ($sorts->isEmpty()) {
            optional($this->defaultSort)->sort($this);
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

    protected function filterDuplicates(Collection $sorts): Collection
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

    protected function findSort(string $property): ?Sort
    {
        return $this->allowedSorts
            ->merge([$this->defaultSort])
            ->first(function (Sort $sort) use ($property) {
                return $sort->isForProperty($property);
            });
    }

    protected function addDefaultSorts()
    {
        $this->allowedSorts = collect($this->request->sorts($this->defaultSort))
            ->map(function ($sort) {
                if ($sort instanceof Sort) {
                    return $sort;
                }

                return Sort::field(ltrim($sort, '-'));
            });

        $this->parseSorts();
    }

    protected function guardAgainstUnknownSorts()
    {
        $sortNames = $this->request->sorts()->map(function ($sort) {
            return ltrim($sort, '-');
        });

        $allowedSortNames = $this->allowedSorts->map->getProperty();

        $diff = $sortNames->diff($allowedSortNames);

        if ($diff->count()) {
            throw InvalidSortQuery::sortsNotAllowed($diff, $allowedSortNames);
        }
    }

}
