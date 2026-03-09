<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\WildcardNotAllowedInEnvironment;

trait FiltersQuery
{
    protected Collection $allowedFilters;

    public function allowedFilters(AllowedFilter|string ...$filters): static
    {
        if (count($filters) === 1 && $filters[0] === '*') {
            if (! app()->environment('local', 'testing')) {
                throw WildcardNotAllowedInEnvironment::create(app()->environment());
            }

            $this->allowedFilters = $this->request->filters()->keys()->map(
                fn (string $filter) => AllowedFilter::partial($filter)
            );

            $this->addFiltersToQuery();

            return $this;
        }

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

    protected function addFiltersToQuery(): void
    {
        $this->allowedFilters->each(function (AllowedFilter $filter) {
            if ($this->isFilterRequested($filter)) {
                $value = $this->request->filters()->get($filter->getName());
                $filter->filter($this, $value);

                return;
            }

            if ($filter->hasDefault()) {
                $filter->filter($this, $filter->getDefault());
            }
        });
    }

    protected function findFilter(string $property): ?AllowedFilter
    {
        return $this->allowedFilters
            ->first(fn (AllowedFilter $filter) => $filter->isForFilter($property));
    }

    protected function isFilterRequested(AllowedFilter $allowedFilter): bool
    {
        return $this->request->filters()->has($allowedFilter->getName());
    }

    protected function ensureAllFiltersExist(): void
    {
        if (config('query-builder.disable_invalid_filter_query_exception', false)) {
            return;
        }

        $filterNames = $this->request->filters()->keys();

        $allowedFilterNames = $this->allowedFilters->map(fn (AllowedFilter $allowedFilter) => $allowedFilter->getName());

        $diff = $filterNames->diff($allowedFilterNames);

        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }
}
