<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\Sort;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\ColumnNameSanitizer;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

trait SortsQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $defaultSorts;

    /** @var \Illuminate\Support\Collection */
    protected $allowedSorts;

    /** @var bool */
    protected $sortsWereParsed = false;

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

        return $this;
    }

    protected function parseSorts()
    {
        // Avoid repeated calls when used by e.g. 'paginate'
        if ($this->sortsWereParsed) {
            return;
        }

        if (! $this->allowedSorts instanceof Collection) {
            $this->addDefaultSorts();
        }

        $sorts = $this->request->sorts();

        if ($sorts->isEmpty()) {
            optional($this->defaultSorts)->each(function (Sort $sort) {
                $sort->sort($this);
            });
        }

        $sorts
            ->each(function (string $property) {
                $descending = $property[0] === '-';

                $key = ltrim($property, '-');

                $sort = $this->findSort($key);

                $sort->sort($this, $descending);
            });

        $this->sortsWereParsed = true;
    }

    protected function findSort(string $property): ?Sort
    {
        return $this->allowedSorts
            ->merge($this->defaultSorts)
            ->first(function (Sort $sort) use ($property) {
                return $sort->isForProperty($property);
            });
    }

    protected function addDefaultSorts()
    {
        $this->allowedSorts = $this->request->sorts()
            ->map(function ($sort) {
                $sortColumn = ltrim($sort, '-');

                // This is the only place where query string parameters are passed as
                // sort columns directly. We need to sanitize these column names.
                $sortColumn = ColumnNameSanitizer::sanitize($sortColumn);

                return Sort::field($sortColumn);
            });
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
}
