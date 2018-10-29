<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;

class QueryBuilder extends Builder
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFilters;

    /** @var string|null */
    protected $defaultSort;

    /** @var \Illuminate\Support\Collection */
    protected $allowedSorts;

    /** @var \Illuminate\Support\Collection */
    protected $allowedIncludes;

    /** @var \Illuminate\Support\Collection */
    protected $allowedAppends;

    /** @var \Illuminate\Support\Collection */
    protected $fields;

    /** @var array */
    protected $appends = [];

    /** @var \Illuminate\Http\Request */
    protected $request;

    /**
     * QueryBuilder constructor.
     * @param Builder $builder
     * @param Request|null $request
     */
    public function __construct(Builder $builder, ? Request $request = null)
    {
        parent::__construct(clone $builder->getQuery());

        $this->initializeFromBuilder($builder);

        $this->request = $request ?? app(Request::class);

        $this->parseSelectedFields();

        if ($this->request->get('sorts')) {
            $this->allowedSorts('*');
        }
    }

    /**
     * Add the model, scopes, eager loaded relationships, local macro's and onDelete callback
     * from the $builder to this query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     */
    protected function initializeFromBuilder(Builder $builder)
    {
        $this->setModel($builder->getModel())
            ->setEagerLoads($builder->getEagerLoads());

        $builder->macro('getProtected', function (Builder $builder, string $property) {
            return $builder->{$property};
        });

        $this->scopes = $builder->getProtected('scopes');

        $this->localMacros = $builder->getProtected('localMacros');

        $this->onDelete = $builder->getProtected('onDelete');
    }

    /**
     * Create a new QueryBuilder for a request and model.
     *
     * @param string|\Illuminate\Database\Query\Builder $baseQuery Model class or base query builder
     * @param Request $request
     *
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    public static function for($baseQuery, ? Request $request = null) : self
    {
        if (is_string($baseQuery)) {
            $baseQuery = ($baseQuery)::query();
        }

        return new static($baseQuery, $request ?? app(Request::class));
    }

    /**
     * @param $filters
     * @return QueryBuilder
     */
    public function allowedFilters($filters) : self
    {
        $filters = is_array($filters) ? $filters : func_get_args();
        $this->allowedFilters = collect($filters)->map(function ($filter) {
            if ($filter instanceof Filter) {
                return $filter;
            }

            return Filter::partial($filter);
        });

        $this->guardAgainstUnknownFilters();

        $this->addFiltersToQuery(new Collection($this->request->get('filters')));

        return $this;
    }

    /**
     * @param $sort
     * @return QueryBuilder
     */
    public function defaultSort($sort) : self
    {
        $this->defaultSort = $sort;

        $this->addSortsToQuery(new Collection($this->request->get('sorts', $this->defaultSort)));

        return $this;
    }

    /**
     * @param $sorts
     * @return QueryBuilder
     */
    public function allowedSorts($sorts) : self
    {
        $sorts = is_array($sorts) ? $sorts : func_get_args();
        if (! $this->request->get('sorts')) {
            return $this;
        }

        $this->allowedSorts = collect($sorts);

        if (! $this->allowedSorts->contains('*')) {
            $this->guardAgainstUnknownSorts();
        }

        $this->addSortsToQuery( new Collection($this->request->get('sorts', $this->defaultSort)));

        return $this;
    }

    /**
     * @param $includes
     * @return QueryBuilder
     */
    public function allowedIncludes($includes) : self
    {
        $includes = is_array($includes) ? $includes : func_get_args();

        $this->allowedIncludes = collect($includes)
            ->flatMap(function ($include) {
                return collect(explode('.', $include))
                    ->reduce(function ($collection, $include) {
                        if ($collection->isEmpty()) {
                            return $collection->push($include);
                        }

                        return $collection->push("{$collection->last()}.{$include}");
                    }, collect());
            });

        $this->guardAgainstUnknownIncludes();

        $this->addIncludesToQuery( new Collection($this->request->get('includes')));

        return $this;
    }

    /**
     * @param $appends
     * @return QueryBuilder
     */
    public function allowedAppends($appends) : self
    {
        $appends = is_array($appends) ? $appends : func_get_args();

        $this->allowedAppends = collect($appends);

        $this->guardAgainstUnknownAppends();

        $this->appends =  new Collection($this->request->get('appends'));

        return $this;
    }

    /**
     * @return void
     */
    protected function parseSelectedFields()
    {
        $this->fields = new Collection($this->request->get('fields'));

        $modelTableName = $this->getModel()->getTable();
        $modelFields = $this->fields->get($modelTableName);

        if (! $modelFields) {
            $modelFields = '*';
        }

        $this->select($this->prependFieldsWithTableName(explode(',', $modelFields), $modelTableName));
    }

    /**
     * @param array $fields
     * @param string $tableName
     * @return array
     */
    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return "{$tableName}.{$field}";
        }, $fields);
    }

    /**
     * @param string $relation
     * @return array
     */
    protected function getFieldsForRelatedTable(string $relation): array
    {
        $fields = $this->fields->get($relation);

        if (! $fields) {
            return [];
        }

        return explode(',', $fields);
    }

    /**
     * @param Collection $filters
     */
    protected function addFiltersToQuery(Collection $filters)
    {
        $filters->each(function ($value, $property) {
            $filter = $this->findFilter($property);

            $filter->filter($this, $value);
        });
    }

    /**
     * @param string $property
     * @return null|Filter
     */
    protected function findFilter(string $property) : ? Filter
    {
        return $this->allowedFilters
            ->first(function (Filter $filter) use ($property) {
                return $filter->isForProperty($property);
            });
    }

    /**
     * @param Collection $sorts
     */
    protected function addSortsToQuery(Collection $sorts)
    {
        $this->filterDuplicates($sorts)
            ->each(function (string $sort) {
                $descending = $sort[0] === '-';

                $key = ltrim($sort, '-');

                $this->orderBy($key, $descending ? 'desc' : 'asc');
            });
    }

    /**
     * @param Collection $sorts
     * @return Collection
     */
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

    /**
     * @param Collection $includes
     */
    protected function addIncludesToQuery(Collection $includes)
    {
        $includes
            ->map('camel_case')
            ->map(function (string $include) {
                return collect(explode('.', $include));
            })
            ->flatMap(function (Collection $relatedTables) {
                return $relatedTables
                    ->mapWithKeys(function ($table, $key) use ($relatedTables) {
                        $fields = $this->getFieldsForRelatedTable(snake_case($table));

                        $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

                        if (empty($fields)) {
                            return [$fullRelationName];
                        }

                        return [$fullRelationName => function ($query) use ($fields) {
                            $query->select($fields);
                        }];
                    });
            })
            ->pipe(function (Collection $withs) {
                $this->with($withs->all());
            });
    }

    /**
     * @param $result
     * @return mixed
     */
    public function setAppendsToResult($result)
    {
        $result->map(function ($item) {
            $item->append($this->appends->toArray());

            return $item;
        });

        return $result;
    }

    /**
     * @return void
     */
    protected function guardAgainstUnknownFilters()
    {
        $filters = new Collection($this->request->get('filters'));

        $filterNames = $filters->keys();

        $allowedFilterNames = $this->allowedFilters->map->getProperty();

        $diff = $filterNames->diff($allowedFilterNames);

        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }

    /**
     * @return void
     */
    protected function guardAgainstUnknownSorts()
    {
        $sorts = (new Collection($this->request->get('sorts')))->map(function ($sort) {
            return ltrim($sort, '-');
        });

        $diff = $sorts->diff($this->allowedSorts);

        if ($diff->count()) {
            throw InvalidSortQuery::sortsNotAllowed($diff, $this->allowedSorts);
        }
    }

    /**
     * @return void
     */
    protected function guardAgainstUnknownIncludes()
    {
        $includes = new Collection($this->request->get('includes'));

        $diff = $includes->diff($this->allowedIncludes);

        if ($diff->count()) {
            throw InvalidIncludeQuery::includesNotAllowed($diff, $this->allowedIncludes);
        }
    }

    /**
     * @return void
     */
    protected function guardAgainstUnknownAppends()
    {
        $appends = new Collection($this->request->get('appends'));

        $diff = $appends->diff($this->allowedAppends);

        if ($diff->count()) {
            throw InvalidAppendQuery::appendsNotAllowed($diff, $this->allowedAppends);
        }
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function get($columns = ['*'])
    {
        $result = parent::get($columns);

        if (count($this->appends) > 0) {
            $result = $this->setAppendsToResult($result);
        }

        return $result;
    }
}
