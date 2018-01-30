<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidFilterQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    private $unknownFilters;

    /** @var \Illuminate\Support\Collection */
    private $allowedFilters;

    public function __construct(Collection $unknownFilters, Collection $allowedFilters)
    {
        $this->unknownFilters = $unknownFilters;
        $this->allowedFilters = $allowedFilters;

        $unknownFilters = $this->unknownFilters->implode(', ');
        $allowedFilters = $this->allowedFilters->implode(', ');
        $message = "Given filter(s) `{$unknownFilters}` are not allowed. Allowed filters are `{$allowedFilters}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function filtersNotAllowed(Collection $unknownFilters, Collection $allowedFilters)
    {
        return new static(...func_get_args());
    }

    public function getUnknownFilters(): Collection
    {
        return $this->unknownFilters;
    }

    public function getAllowedFilters(): Collection
    {
        return $this->allowedFilters;
    }
}
