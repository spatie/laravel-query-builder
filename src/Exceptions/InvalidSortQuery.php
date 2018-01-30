<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidSortQuery extends InvalidQuery
{
    /** @var string */
    public $unknownSort;

    /** @var \Illuminate\Support\Collection */
    public $allowedSorts;

    public function __construct(string $unknownSort, Collection $allowedSorts)
    {
        $this->unknownSort = $unknownSort;
        $this->allowedSorts = $allowedSorts;

        $allowedSorts = $allowedSorts->implode(', ');
        $message = "Given sort `{$this->unknownSort}` is not allowed. Allowed sorts are `{$allowedSorts}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function sortsNotAllowed(string $unknownSort, Collection $allowedSorts)
    {
        return new static(...func_get_args());
    }
}
