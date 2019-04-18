<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\Sort;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

trait SortsQuery
{
    /** @var \Illuminate\Support\Collection */
    private $defaultSorts;

    /** @var \Illuminate\Support\Collection */
    private $allowedSorts;

    /** @var bool */
    protected $sortsWereParsed = false;

    /**
     * Per default, sorting is allowed on all columns if not specified otherwise.
     * We keep track of those default sorts to purge them if, at a later point in time, allowed sorts are specified.
     *
     * @var array
     */
    protected $generatedDefaultSorts = [];

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
        // Avoid repeated calls when used by e.g. 'paginate'
        if ($this->sortsWereParsed) {
            return;
        }

        $this->sortsWereParsed = true;

        if (! $this->allowedSorts instanceof Collection) {
            $this->addDefaultSorts();
            $this->allowRepeatedParse();
        } else {
            $this->purgeGeneratedDefaultSorts();
        }

        $sorts = $this->request->sorts();

        if ($sorts->isEmpty()) {
            $this->addDefaultSortsToQuery();

            return;
        }

        $sorts
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
        $this->sortsWereParsed = false;
    }

    protected function purgeGeneratedDefaultSorts(): void
    {
        $this->query->orders = collect($this->query->orders)
            ->reject(function ($order) {
                return in_array($order['column'], $this->generatedDefaultSorts);
            })->values()->all();
    }

    private function addDefaultSortsToQuery(): void
    {
        optional($this->defaultSorts)->each(function (Sort $sort) {
            $sort->sort($this);
        });
    }
}
