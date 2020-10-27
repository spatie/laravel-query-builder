<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;

trait FiltersQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFilters;

    public function allowedFilters($filters): self
    {
        $filters = is_array($filters) ? $filters : func_get_args();

        $this->allowedFilters = collect($filters)->map(function ($filter) {
            if ($filter instanceof AllowedFilter) {
                return $filter;
            }

            return AllowedFilter::partial($filter);
        });

        $this->ensureAllFiltersExist();

        $this->addFiltersToQuery();

        return $this;
    }

    protected function addFiltersToQuery()
    {
        $this->allowedFilters->each(function (AllowedFilter $filter) {
            if ($this->isFilterRequested($filter)) {
                $value = $this->request->filters()->get($filter->getName());
                $filter->filter($this, $value);

                return;
            }

            if ($filter->hasDefault()) {
                $filter->filter($this, $filter->getDefault());

                return;
            }
        });
    }

    protected function findFilter(string $property): ?AllowedFilter
    {
        return $this->allowedFilters
            ->first(function (AllowedFilter $filter) use ($property) {
                return $filter->isForFilter($property);
            });
    }

    protected function isFilterRequested(AllowedFilter $allowedFilter): bool
    {
        return $this->request->filters()->has($allowedFilter->getName());
    }

    protected function ensureAllFiltersExist()
    {
        if (config('query-builder.disable_invalid_filter_query_exception')) {
            return;
        }

        $filterNames = $this->request->filters()->keys();

        $allowedFilterNames = $this->allowedFilters->map(function (AllowedFilter $allowedFilter) {
            return $allowedFilter->getName();
        });

        $diff = $filterNames->diff($allowedFilterNames);

        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }
}
