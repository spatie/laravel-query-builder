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

    /** @var string */
    protected $modelTableName;

    /** @var array|null */
    private $joinColumnConflicts = null;

    public function __construct(Builder $builder, ? Request $request = null)
    {
        parent::__construct(clone $builder->getQuery());

        $this->initializeFromBuilder($builder);

        $this->request = $request ?? request();

        $this->modelTableName = $this->getModel()->getTable();

        if ($this->request->fields()->isNotEmpty()) {
            $this->parseSelectedFields();
        }

        if ($this->request->sorts()->isNotEmpty()) {
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

        return new static($baseQuery, $request ?? request());
    }

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

        $this->addFiltersToQuery($this->request->filters());

        return $this;
    }

    public function allowedFields($fields) : self
    {
        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                if (! str_contains($fieldName, '.')) {
                    return "{$this->modelTableName}.{$fieldName}";
                }

                return $fieldName;
            });

        if (! $this->allowedFields->contains('*')) {
            $this->guardAgainstUnknownFields();
        }

        return $this;
    }

    public function defaultSort($sort) : self
    {
        $this->defaultSort = $sort;

        $this->addSortsToQuery($this->request->sorts($this->defaultSort));

        return $this;
    }

    public function allowedSorts($sorts) : self
    {
        $sorts = is_array($sorts) ? $sorts : func_get_args();
        if (! $this->request->sorts()) {
            return $this;
        }

        $this->allowedSorts = collect($sorts);

        if (! $this->allowedSorts->contains('*')) {
            $this->guardAgainstUnknownSorts();
        }

        $this->addSortsToQuery($this->request->sorts($this->defaultSort));

        return $this;
    }

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

        $this->addIncludesToQuery($this->request->includes());

        return $this;
    }

    public function allowedAppends($appends) : self
    {
        $appends = is_array($appends) ? $appends : func_get_args();

        $this->allowedAppends = collect($appends);

        $this->guardAgainstUnknownAppends();

        $this->appends = $this->request->appends();

        return $this;
    }

    protected function parseSelectedFields()
    {
        $this->fields = $this->request->fields();

        $modelFields = $this->fields->get($this->modelTableName, ['*']);

        $this->select($this->prependFieldsWithTableName($modelFields, $this->modelTableName));
    }

    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return "{$tableName}.{$field}";
        }, $fields);
    }

    protected function getFieldsForRelatedTable(string $relation): array
    {
        if (! $this->fields) {
            return ['*'];
        }

        return $this->fields->get($relation, []);
    }

    protected function addFiltersToQuery(Collection $filters)
    {
        $filters->each(function ($value, $property) {
            $filter = $this->findFilter($property);

            $filter->filter($this, $value);
        });
    }

    protected function findFilter(string $property) : ? Filter
    {
        return $this->allowedFilters
            ->first(function (Filter $filter) use ($property) {
                return $filter->isForProperty($property);
            });
    }

    protected function addSortsToQuery(Collection $sorts)
    {
        $this->getJoinColumnConflicts();

        $this->filterDuplicates($sorts)
            ->each(function (string $sort) {
                $descending = $sort[0] === '-';

                $key = ltrim($sort, '-');

                if (in_array($key, $this->joinColumnConflicts)) {
                    $key = "{$this->modelTableName}.{$key}";
                }

                $this->orderBy($key, $descending ? 'desc' : 'asc');
            });
    }

    protected function filterDuplicates(Collection $sorts): Collection
    {
        if (! is_array($orders = $this->getQuery()->orders)) {
            return $sorts;
        }

        $this->getJoinColumnConflicts();

        return $sorts->reject(function (string $sort) use ($orders) {
            $sortColumn = ltrim($sort, '-');

            if (in_array($sortColumn, $this->joinColumnConflicts)) {
                $sortColumn = "{$this->modelTableName}.{$sortColumn}";
            }

            $toSort = [
                'column' => $sortColumn,
                'direction' => ($sort[0] === '-') ? 'desc' : 'asc',
            ];
            foreach ($orders as $order) {
                if ($order === $toSort) {
                    return true;
                }
            }
        });
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
                        $fields = $this->getFieldsForRelatedTable(snake_case($table));

                        $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

                        if (empty($fields)) {
                            return [$fullRelationName];
                        }

                        return [$fullRelationName => function ($query) use ($fields) {
                            $query->select($this->prependFieldsWithTableName($fields, $query->getModel()->getTable()));
                        }];
                    });
            })
            ->pipe(function (Collection $withs) {
                $this->with($withs->all());
            });
    }

    public function setAppendsToResult($result)
    {
        $result->map(function ($item) {
            $item->append($this->appends->toArray());

            return $item;
        });

        return $result;
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
        $sorts = $this->request->sorts()->map(function ($sort) {
            return ltrim($sort, '-');
        });

        $diff = $sorts->diff($this->allowedSorts);

        if ($diff->count()) {
            throw InvalidSortQuery::sortsNotAllowed($diff, $this->allowedSorts);
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

    public function get($columns = ['*'])
    {
        $result = parent::get($columns);

        if (count($this->appends) > 0) {
            $result = $this->setAppendsToResult($result);
        }

        return $result;
    }

    public function getDbColumns(string $tableName)
    {
        return \Schema::getColumnListing($tableName);
    }

    /**
     * Get the names of the columns in the selected table that have the same
     * name as a column in a JOINed table.
     *
     * @return array
     */
    protected function getJoinColumnConflicts()
    {
        // Check if all of the column conflicts have been gathered before. If
        // so, there is no need to do it again.
        if (is_array($this->joinColumnConflicts)) {
            return $this->joinColumnConflicts;
        }

        $joins = $this->getQuery()->joins;

        // Check if there are any JOIN statements in the query
        if (! is_array($joins)) {
            return $this->joinColumnConflicts = [];
        }

        $joinColumns = [];

        // Get all of the column names of the joined tables
        foreach ($joins as $join) {
            // Only get the first word in the table name just in case the
            // table name contains an AS (Example: "table as my_table")
            $joinTableName = explode(' ', trim($join->table))[0];

            $joinColumns = array_merge($joinColumns, $this->getDbColumns($joinTableName));
        }

        $this->joinColumnConflicts = array_intersect($joinColumns, $this->getDbColumns($this->modelTableName));

        return $this->joinColumnConflicts;
    }
}
