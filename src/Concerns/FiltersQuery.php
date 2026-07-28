<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
trait FiltersQuery
{
    /**
     * @var Collection<array-key, AllowedFilter<TModel>>
     */
    protected Collection $allowedFilters;

    /**
     * @param \Spatie\QueryBuilder\AllowedFilter<TModel>|non-empty-string $filters
     */
    public function allowedFilters(AllowedFilter|string ...$filters): static
    {
        $this->allowedFilters = collect($filters)->map(static function ($filter) {
            if ($filter instanceof AllowedFilter) {
                return $filter;
            }

            /**
             * @var AllowedFilter<TModel>
             */
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

    /**
     * @return AllowedFilter<TModel>
     */
    protected function findFilter(string $property): ?AllowedFilter
    {
        return $this->allowedFilters
            ->first(fn (AllowedFilter $filter) => $filter->isForFilter($property));
    }

    /**
     * @param AllowedFilter<TModel> $allowedFilter
     */
    protected function isFilterRequested(AllowedFilter $allowedFilter): bool
    {
        return $this->request->filters()->has($allowedFilter->getName());
    }

    protected function ensureAllFiltersExist(): void
    {
        $shouldSkip = config()->boolean('query-builder.disable_invalid_filter_query_exception', false);

        if ($shouldSkip) {
            return;
        }

        $filterNames = $this->request->filters()->keys();

        $allowedFilterNames = $this->allowedFilters->map(
            static fn (AllowedFilter $allowedFilter) => $allowedFilter->getName()
        );

        $diff = $filterNames->diff($allowedFilterNames);

        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }
}
