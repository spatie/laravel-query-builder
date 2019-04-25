<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidFilterQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownFilters;

    /** @var \Illuminate\Support\Collection */
    public $allowedFilters;

    public function __construct(Collection $unknownFilters, Collection $allowedFilters)
    {
        $this->unknownFilters = $unknownFilters;
        $this->allowedFilters = $allowedFilters;

        $unknownFilters = $this->unknownFilters->implode(', ');
        $allowedFilters = $this->allowedFilters->implode(', ');
        $message = "Requested filter(s) `{$unknownFilters}` are not allowed. Allowed filter(s) are `{$allowedFilters}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function filtersNotAllowed(Collection $unknownFilters, Collection $allowedFilters)
    {
        return new static(...func_get_args());
    }
}
