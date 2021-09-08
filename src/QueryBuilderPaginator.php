<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Traits\ForwardsCalls;

class QueryBuilderPaginator
{
    use ForwardsCalls;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * @var \Spatie\QueryBuilder\QueryBuilderRequest
     */
    protected $request;

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $subject
     */
    public function __construct($subject, $request)
    {
        $this->query = $subject instanceof Relation ? $subject->getQuery() : $subject;
        $this->request = $request;
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->query->paginate($perPage, $columns, $pageName, $page)->appends(
            $this->getQueryBuilderParamsFromRequest()
        );
    }

    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return $this->query->simplePaginate($perPage, $columns, $pageName, $page)->appends(
            $this->getQueryBuilderParamsFromRequest()
        );
    }

    public function cursorPaginate($perPage = null, $columns = ['*'], $cursorName = 'cursor', $cursor = null)
    {
        return $this->query->cursorPaginate($perPage, $columns, $cursorName, $cursor)->appends(
            $this->getQueryBuilderParamsFromRequest()
        );
    }

    private function getQueryBuilderParamsFromRequest()
    {
        return array_filter(
            array_merge(
                $this->request->filters()->mapWithKeys(function ($value, $key) {
                    return [config('query-builder.parameters.filter') . "[${key}]" => $value];
                })->toArray(),
                [
                    config('query-builder.parameters.sort') => $this->request->sorts()->join(
                        QueryBuilderRequest::getIncludesArrayValueDelimiter()
                    )
                ],
                [
                    config('query-builder.parameters.append') => $this->request->appends()->join(
                        QueryBuilderRequest::getAppendsArrayValueDelimiter()
                    )
                ],
                [
                    config('query-builder.parameters.include') => $this->request->includes()->join(
                        QueryBuilderRequest::getIncludesArrayValueDelimiter()
                    )
                ],
                $this->request->fields()->mapWithKeys(function ($values, $key) {
                    return [config('query-builder.parameters.fields') . "[${key}]" => implode(",", $values)];
                })->toArray(),
            )
        );
    }

    public function __call($method, $arguments)
    {
        return $this->forwardCallTo($this->query, $method, $arguments);
    }
}
