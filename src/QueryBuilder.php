<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Concerns\SortsQuery;
use Spatie\QueryBuilder\Concerns\FiltersQuery;
use Spatie\QueryBuilder\Concerns\AddsFieldsToQuery;
use Spatie\QueryBuilder\Concerns\AddsIncludesToQuery;
use Spatie\QueryBuilder\Concerns\AppendsAttributesToResults;

class QueryBuilder extends Builder
{
    use FiltersQuery,
        SortsQuery,
        AddsIncludesToQuery,
        AddsFieldsToQuery,
        AppendsAttributesToResults;

    /** @var \Spatie\QueryBuilder\QueryBuilderRequest */
    protected $request;

    public function __construct(Builder $builder, ? Request $request = null)
    {
        parent::__construct(clone $builder->getQuery());

        $this->initializeFromBuilder($builder);

        $this->request = QueryBuilderRequest::fromRequest($request ?? request());
    }

    /**
     * Create a new QueryBuilder for a request and model.
     *
     * @param string|\Illuminate\Database\Query\Builder $baseQuery Model class or base query builder
     * @param \Illuminate\Http\Request                  $request
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

    public function getQuery()
    {
        $this->parseSorts();

        if (! $this->allowedFields instanceof Collection) {
            $this->addAllRequestedFields();
        }

        return parent::getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function get($columns = ['*'])
    {
        $this->parseSorts();

        if (! $this->allowedFields instanceof Collection) {
            $this->addAllRequestedFields();
        }

        $results = parent::get($columns);

        if ($this->request->appends()->isNotEmpty()) {
            $results = $this->addAppendsToResults($results);
        }

        return $results;
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->parseSorts();

        if (! $this->allowedFields instanceof Collection) {
            $this->addAllRequestedFields();
        }

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->parseSorts();

        if (! $this->allowedFields instanceof Collection) {
            $this->addAllRequestedFields();
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
}
