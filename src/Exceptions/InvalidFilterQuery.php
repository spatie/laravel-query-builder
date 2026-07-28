<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidFilterQuery extends InvalidQuery
{
    /**
     * @param Collection<int,non-empty-string> $unknownFilters
     * @param Collection<int,non-empty-string> $allowedFilters
     */
    public function __construct(
        public Collection $unknownFilters,
        public Collection $allowedFilters
    ) {
        $unknownFilters = $this->unknownFilters->implode(', ');
        $allowedFilters = $this->allowedFilters->implode(', ');
        $message = "Requested filter(s) `{$unknownFilters}` are not allowed. Allowed filter(s) are `{$allowedFilters}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    /**
     * @param Collection<int,non-empty-string> $unknownFilters
     * @param Collection<int,non-empty-string> $allowedFilters
     */
    public static function filtersNotAllowed(Collection $unknownFilters, Collection $allowedFilters): static
    {
        return new static(...func_get_args());
    }
}
