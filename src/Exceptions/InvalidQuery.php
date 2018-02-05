<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidQuery extends HttpException
{
    public static function filtersNotAllowed(Collection $unknownFilters, Collection $allowedFilters)
    {
        $unknownFilters = $unknownFilters->implode(', ');
        $allowedFilters = $allowedFilters->implode(', ');

        $message = "Given filter(s) `{$unknownFilters}` are not allowed. Allowed filters are `{$allowedFilters}`.";

        return new static(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function sortsNotAllowed(Collection $unknownSorts, Collection $allowedSorts)
    {
        $unknownSorts = $unknownSorts->implode(', ');
        $allowedSorts = $allowedSorts->implode(', ');

        $message = "Given sort(s) `{$unknownSorts}` is not allowed. Allowed sorts are `{$allowedSorts}`.";

        return new static(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function includesNotAllowed(Collection $unknownIncludes, Collection $allowedIncludes)
    {
        $unknownIncludes = $unknownIncludes->implode(', ');
        $allowedIncludes = $allowedIncludes->implode(', ');

        $message = "Given include(s) `{$unknownIncludes}` are not allowed. Allowed includes are `{$allowedIncludes}`.";

        return new static(Response::HTTP_BAD_REQUEST, $message);
    }
}
