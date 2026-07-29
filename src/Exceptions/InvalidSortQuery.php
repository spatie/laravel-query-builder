<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * @consistent-constructor
 */
class InvalidSortQuery extends InvalidQuery
{
    /**
     * @param  Collection<array-key, mixed>  $unknownSorts
     * @param  Collection<array-key, mixed>  $allowedSorts
     */
    public function __construct(
        public Collection $unknownSorts,
        public Collection $allowedSorts
    ) {
        $allowedSorts = $allowedSorts->implode(', ');
        $unknownSorts = $unknownSorts->implode(', ');

        $message = "Requested sort(s) `{$unknownSorts}` is not allowed. Allowed sort(s) are `{$allowedSorts}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    /**
     * @param  Collection<array-key, mixed>  $unknownSorts
     * @param  Collection<array-key, mixed>  $allowedSorts
     */
    public static function sortsNotAllowed(Collection $unknownSorts, Collection $allowedSorts): static
    {
        return new static(...func_get_args());
    }
}
