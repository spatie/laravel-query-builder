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

        $message = __('query-builder::errors.filters', ['unknown' => $unknownFilters, 'allowed' => $allowedFilters]);

        return new static(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function sortsNotAllowed(string $unknownSort, Collection $allowedSorts)
    {
        $allowedSorts = $allowedSorts->implode(', ');

        $message = __('query-builder::errors.sorts', ['unknown' => $unknownSort, 'allowed' => $allowedSorts]);

        return new static(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function includesNotAllowed(Collection $unknownIncludes, Collection $allowedIncludes)
    {
        $unknownIncludes = $unknownIncludes->implode(', ');
        $allowedIncludes = $allowedIncludes->implode(', ');

        $message = __('query-builder::errors.includes', ['unknown' => $unknownIncludes, 'allowed' => $allowedIncludes]);

        return new static(Response::HTTP_BAD_REQUEST, $message);
    }
}
