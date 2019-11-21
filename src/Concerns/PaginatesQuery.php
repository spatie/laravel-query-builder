<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Contracts\Pagination\Paginator;

/**
 * PaginatesQuery provides enhanced query pagination mechanism.
 *
 * It allows control over page size via request parameter and pagination parameters setup via 'query-builder' config.
 *
 * @mixin \Spatie\QueryBuilder\QueryBuilder
 */
trait PaginatesQuery
{
    /**
     * Paginates the query.
     * @see \Illuminate\Database\Eloquent\Builder::paginate()
     *
     * @param  array|int|null  $perPage per page options, refer to {@see getPerPageValue()} for more details.
     * @param  array  $columns
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function pagination($perPage = null, $columns = ['*']) : Paginator
    {
        return $this->paginate($this->getPerPageValue($perPage), $columns, $this->getPageParameterName(), $this->getPageValue());
    }

    /**
     * Paginate the query into a simple paginator.
     * @see \Illuminate\Database\Eloquent\Builder::simplePaginate()
     *
     * @param  array|int|null  $perPage per page options, refer to {@see getPerPageValue()} for more details.
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePagination($perPage = null, $columns = ['*']) : Paginator
    {
        return $this->simplePaginate($this->getPerPageValue($perPage), $columns, $this->getPageParameterName(), $this->getPageValue());
    }

    /**
     * Extracts per page value from the related HTTP request.
     *
     * @param  array|int|null  $options if not set - use default options, if scalar - use it as default per page value.
     * If array - use as options with following keys:
     *
     * - 'default' - int, default per page value.
     * - 'min' - int, min allowed per page value.
     * - 'max' - int, max allowed per page value.
     *
     * @return int per page value.
     */
    protected function getPerPageValue($options = null): int
    {
        $defaultOptions = [
            'default' => config('query-builder.pagination.per-page.default', 15),
            'min' => config('query-builder.pagination.per-page.min', 1),
            'max' => config('query-builder.pagination.per-page.max', 50),
        ];

        if (is_array($options)) {
            $options = array_merge($defaultOptions, $options);
        } elseif ($options === null) {
            $options = $defaultOptions;
        } else {
            $options = array_merge($defaultOptions, ['default' => $options]);
        }

        $options = array_map(function ($value) {
            return (int) $value;
        }, $options);

        $perPageParameterName = config('query-builder.parameters.per-page', 'per-page');

        $perPage = (int) $this->request->query($perPageParameterName, $options['default']);

        if ($perPage < $options['min']) {
            return $options['min'];
        }

        if ($perPage > $options['max']) {
            return $options['max'];
        }

        return $perPage;
    }

    /**
     * @return string HTTP query parameter name for page number.
     */
    protected function getPageParameterName() : string
    {
        return config('query-builder.parameters.page', 'page');
    }

    /**
     * @return int|null page number passed within associated HTTP request.
     */
    protected function getPageValue()
    {
        return $this->request->query($this->getPageParameterName());
    }
}
