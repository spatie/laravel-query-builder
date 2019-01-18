<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;

class QueryBuilder extends Builder
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFilters;

    /** @var \Illuminate\Support\Collection */
    protected $allowedFields;

    /** @var \Spatie\QueryBuilder\Sort|null */
    protected $defaultSort;

    /** @var \Illuminate\Support\Collection */
    protected $allowedSorts;

    /** @var \Illuminate\Support\Collection */
    protected $allowedIncludes;

    /** @var \Illuminate\Support\Collection */
    protected $allowedAppends;

    /** @var \Illuminate\Http\Request */
    protected $request;

    public function __construct(Builder $builder, ? Request $request = null)
    {
        parent::__construct(clone $builder->getQuery());

        $this->initializeFromBuilder($builder);

        $this->request = $request ?? request();

        $this->parseFields();
    }

    /**
     * Create a new QueryBuilder for a request and model.
     *
     * @param string|\Illuminate\Database\Query\Builder $baseQuery Model class or base query builder
     * @param \Illuminate\Http\Request                  $request
     *
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    public static function for($baseQuery, ? Request $request = null): self
    {
        if (is_string($baseQuery)) {
            $baseQuery = ($baseQuery)::query();
        }

        return new static($baseQuery, $request ?? request());
    }

    public function allowedFilters($filters): self
    {
        $filters = is_array($filters) ? $filters : func_get_args();
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

    public function allowedFields($fields): self
    {
        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                if (! str_contains($fieldName, '.')) {
                    $modelTableName = $this->getModel()->getTable();

                    return "{$modelTableName}.{$fieldName}";
                }

                return $fieldName;
            });

        if (! $this->allowedFields->contains('*')) {
            $this->guardAgainstUnknownFields();
        }

        return $this;
    }

    public function parseFields()
    {
        $this->addFieldsToQuery($this->request->fields());
    }

    public function allowedIncludes($includes): self
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

        $this->addIncludesToQuery($this->request->includes());

        return $this;
    }

    public function allowedAppends($appends): self
    {
        $appends = is_array($appends) ? $appends : func_get_args();

        $this->allowedAppends = collect($appends);

        $this->guardAgainstUnknownAppends();

        return $this;
    }

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

    public function getQuery()
    {
        if ($this->request->sorts() && ! $this->allowedSorts instanceof Collection) {
            $this->addDefaultSorts();
        }

        return parent::getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function get($columns = ['*'])
    {
        if ($this->request->sorts() && ! $this->allowedSorts instanceof Collection) {
            $this->addDefaultSorts();
        }

        $results = parent::get($columns);

        if ($this->request->appends()->isNotEmpty()) {
            $results = $this->addAppendsToResults($results);
        }

        return $results;
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        if ($this->request->sorts() && ! $this->allowedSorts instanceof Collection) {
            $this->addDefaultSorts();
        }

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        if ($this->request->sorts() && ! $this->allowedSorts instanceof Collection) {
            $this->addDefaultSorts();
        }

        return parent::simplePaginate($perPage, $columns, $pageName, $page);
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

    protected function addFieldsToQuery(Collection $fields)
    {
        $modelTableName = $this->getModel()->getTable();

        if ($modelFields = $fields->get($modelTableName)) {
            $this->select($this->prependFieldsWithTableName($modelFields, $modelTableName));
        }
    }

    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return "{$tableName}.{$field}";
        }, $fields);
    }

    protected function getFieldsForIncludedTable(string $relation): array
    {
        return $this->request->fields()->get($relation, []);
    }

    protected function addFiltersToQuery(Collection $filters)
    {
        $filters->each(function ($value, $property) {
            $filter = $this->findFilter($property);

            $filter->filter($this, $value);
        });
    }

    protected function findFilter(string $property): ?Filter
    {
        return $this->allowedFilters
            ->first(function (Filter $filter) use ($property) {
                return $filter->isForProperty($property);
            });
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

    protected function addAppendsToResults(Collection $results)
    {
        $appends = $this->request->appends();

        return $results->each->append($appends->toArray());
    }

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
                        $fields = $this->getFieldsForIncludedTable(snake_case($table));

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

    protected function guardAgainstUnknownFilters()
    {
        $filterNames = $this->request->filters()->keys();

        $allowedFilterNames = $this->allowedFilters->map->getProperty();

        $diff = $filterNames->diff($allowedFilterNames);

        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }

    protected function guardAgainstUnknownFields()
    {
        $fields = $this->request->fields()
            ->map(function ($fields, $model) {
                $tableName = snake_case(preg_replace('/-/', '_', $model));

                $fields = array_map('snake_case', $fields);

                return $this->prependFieldsWithTableName($fields, $tableName);
            })
            ->flatten()
            ->unique();

        $diff = $fields->diff($this->allowedFields);

        if ($diff->count()) {
            throw InvalidFieldQuery::fieldsNotAllowed($diff, $this->allowedFields);
        }
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

    protected function guardAgainstUnknownIncludes()
    {
        $includes = $this->request->includes();

        $diff = $includes->diff($this->allowedIncludes);

        if ($diff->count()) {
            throw InvalidIncludeQuery::includesNotAllowed($diff, $this->allowedIncludes);
        }
    }

    protected function guardAgainstUnknownAppends()
    {
        $appends = $this->request->appends();

        $diff = $appends->diff($this->allowedAppends);

        if ($diff->count()) {
            throw InvalidAppendQuery::appendsNotAllowed($diff, $this->allowedAppends);
        }
    }
}
