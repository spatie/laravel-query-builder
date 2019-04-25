<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidSortQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownSorts;

    /** @var \Illuminate\Support\Collection */
    public $allowedSorts;

    public function __construct(Collection $unknownSorts, Collection $allowedSorts)
    {
        $this->unknownSorts = $unknownSorts;
        $this->allowedSorts = $allowedSorts;

        $allowedSorts = $allowedSorts->implode(', ');
        $unknownSorts = $unknownSorts->implode(', ');
        $message = "Requested sort(s) `{$unknownSorts}` is not allowed. Allowed sort(s) are `{$allowedSorts}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function sortsNotAllowed(Collection $unknownSorts, Collection $allowedSorts)
    {
        return new static(...func_get_args());
    }
}
