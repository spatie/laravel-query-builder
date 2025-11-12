<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;

trait FiltersQuery
{
    protected Collection $allowedFilters;

    public function allowedFilters($filters): static
    {
        $filters = is_array($filters) ? $filters : func_get_args();

        $this->allowedFilters = collect($filters)->flatten(1)->map(function ($filter) {
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
        // Apply regular filters (AND logic by default)
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

        // Apply AND filter groups (explicit AND)
        $this->request->andFilters()->each(function ($value, $filterName) {
            $filter = $this->findFilter($filterName);
            if ($filter) {
                $filter->filter($this, $value);
            }
        });

        // Apply OR filter groups (OR logic)
        $orFilters = $this->request->orFilters();
        if ($orFilters->isNotEmpty()) {
            $this->getEloquentBuilder()->where(function ($query) use ($orFilters) {
                $first = true;
                $orFilters->each(function ($value, $filterName) use ($query, &$first) {
                    $filter = $this->findFilter($filterName);
                    if ($filter) {
                        // Create QueryBuilder wrapper for the OR query
                        $orQueryBuilder = \Spatie\QueryBuilder\QueryBuilder::for($query, $this->request);
                        $orQueryBuilder->allowedFilters = $this->allowedFilters;
                        
                        if ($first) {
                            // First filter in OR group uses where
                            $filter->filter($orQueryBuilder, $value);
                            $first = false;
                        } else {
                            // Subsequent filters use orWhere
                            $query->orWhere(function ($orQuery) use ($filter, $value) {
                                $orQueryBuilder = \Spatie\QueryBuilder\QueryBuilder::for($orQuery, $this->request);
                                $orQueryBuilder->allowedFilters = $this->allowedFilters;
                                $filter->filter($orQueryBuilder, $value);
                            });
                        }
                    }
                });
            });
        }
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

    protected function ensureAllFiltersExist(): void
    {
        if (config('query-builder.disable_invalid_filter_query_exception', false)) {
            return;
        }

        $allowedFilterNames = $this->allowedFilters->map(function (AllowedFilter $allowedFilter) {
            return $allowedFilter->getName();
        });

        // Check regular filters
        $filterNames = $this->request->filters()->keys();
        $diff = $filterNames->diff($allowedFilterNames);
        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }

        // Check AND filter groups
        $andFilterNames = $this->request->andFilters()->keys();
        $andDiff = $andFilterNames->diff($allowedFilterNames);
        if ($andDiff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($andDiff, $allowedFilterNames);
        }

        // Check OR filter groups
        $orFilterNames = $this->request->orFilters()->keys();
        $orDiff = $orFilterNames->diff($allowedFilterNames);
        if ($orDiff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($orDiff, $allowedFilterNames);
        }
    }
}
