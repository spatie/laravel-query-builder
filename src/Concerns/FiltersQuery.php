<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Contracts\AllowedFilterContract;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;

trait FiltersQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFilters;

    public function allowedFilters($filters): static
    {
        $filters = is_array($filters) ? $filters : func_get_args();

        $this->allowedFilters = collect($filters)->map(function ($filter) {
            if ($filter instanceof AllowedFilterContract) {
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
        $this->allowedFilters->each(function (AllowedFilterContract $filter) {
            if ($filter->isRequested($this->request)) {
                $value = $filter->getValueFromRequest($this->request);
                $filter->filter($this, $value);

                return;
            }

            if ($filter->hasDefault()) {
                $filter->filter($this, $filter->getDefault());
            }
        });
    }

    protected function ensureAllFiltersExist()
    {
        if (config('query-builder.disable_invalid_filter_query_exception', false)) {
            return;
        }

        $filterNames = $this->request->filters()->keys();

        $allowedFilterNames = $this->allowedFilters->map(function (AllowedFilterContract $allowedFilter) {
            return $allowedFilter->getNames();
        })->flatten();

        $diff = $filterNames->diff($allowedFilterNames);

        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }
}
