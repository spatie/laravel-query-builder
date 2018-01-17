<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidQuery;

class QueryBuilder extends Builder
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFilters;

    /** @var \Illuminate\Support\Collection */
    protected $allowedSorts;

    /** @var \Illuminate\Support\Collection */
    protected $allowedIncludes;

    /** @var \Illuminate\Http\Request */
    protected $request;

    public function __construct(Builder $builder, ?Request $request = null)
    {
        parent::__construct($builder->getQuery());

        $this->setModel($builder->getModel());

        $this->request = $request ?? request();

        if ($this->request->sort()) {
            $this->allowedSorts('*');
        }
    }

    /**
     * Create a new QueryBuilder for a request and model.
     *
     * @param string|\Illuminate\Database\Query\Builder $baseQuery Model class or base query builder
     * @param Request $request
     *
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    public static function for($baseQuery, ?Request $request = null): self
    {
        if (is_string($baseQuery)) {
            $baseQuery = ($baseQuery)::query();
        }

        return new static($baseQuery, $request ?? request());
    }

    public function allowedFilters(...$filters): self
    {
        $this->allowedFilters = collect($filters)->map(function ($filter) {
            if ($filter instanceof Filter) {
                return $filter;
            }

            return Filter::partial($filter);
        });

        $this->guardAgainstUnknownFilters();

        $this->addFiltersToQuery($this->request->filters());

        return $this;
    }

    public function allowedSorts(...$sorts): self
    {
        $this->allowedSorts = collect($sorts);

        if (! $this->allowedSorts->contains('*')) {
            $this->guardAgainstUnknownSorts();
        }

        $this->addSortToQuery($this->request->sort());

        return $this;
    }

    public function allowedIncludes(...$includes): self
    {
        $this->allowedIncludes = collect($includes);

        $this->guardAgainstUnknownIncludes();

        $this->addIncludesToQuery($this->request->includes());

        return $this;
    }

    protected function addFiltersToQuery(Collection $filters)
    {
        $filters->each(function ($value, $property) {
            $filter = $this->findFilter($property);

            $filter->filter($this, $value);
        });
    }

    protected function findFilter(string $property) : ?Filter
    {
        return $this->allowedFilters
            ->first(function (Filter $filter) use ($property) {
                return $filter->isForProperty($property);
            });
    }

    protected function addSortToQuery(string $sort)
    {
        $descending = $sort[0] === '-';

        $key = ltrim($sort, '-');

        $this->orderBy($key, $descending ? 'desc' : 'asc');
    }

    protected function addIncludesToQuery(Collection $includes)
    {
        $includes
            ->map(function (string $include) {
                return camel_case($include);
            })
            ->each(function (string $include) {
                $this->with($include);
            });
    }

    protected function guardAgainstUnknownFilters()
    {
        $filterNames = $this->request->filters()->keys();

        $allowedFilterNames = $this->allowedFilters->map->getProperty();

        $diff = $filterNames->diff($allowedFilterNames);

        if ($diff->count()) {
            throw InvalidQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }

    protected function guardAgainstUnknownSorts()
    {
        $sort = ltrim($this->request->sort(), '-');

        if (! $this->allowedSorts->contains($sort)) {
            throw InvalidQuery::sortsNotAllowed($sort, $this->allowedSorts);
        }
    }

    protected function guardAgainstUnknownIncludes()
    {
        $includes = $this->request->includes();

        $diff = $includes->diff($this->allowedIncludes);

        if ($diff->count()) {
            throw InvalidQuery::includesNotAllowed($diff, $this->allowedIncludes);
        }
    }
}
