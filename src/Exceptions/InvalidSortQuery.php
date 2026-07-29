<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidSortQuery extends InvalidQuery
{
    /**
     * @param Collection<int,non-empty-string> $unknownSorts
     * @param Collection<int,non-empty-string> $allowedSorts
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
     * @param Collection<int,non-empty-string> $unknownSorts
     * @param Collection<int,non-empty-string> $allowedSorts
     */
    public static function sortsNotAllowed(Collection $unknownSorts, Collection $allowedSorts): static
    {
        return new static(...func_get_args());
    }
}
