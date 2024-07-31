<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidAppendQuery extends InvalidQuery
{
    public function __construct(
        public Collection $appendsNotAllowed,
        public Collection $allowedAppends
    ) {
        $appendsNotAllowed = $appendsNotAllowed->implode(', ');
        $allowedAppends = $allowedAppends->implode(', ');
        $message = "Requested append(s) `{$appendsNotAllowed}` are not allowed. Allowed append(s) are `{$allowedAppends}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function appendsNotAllowed(Collection $appendsNotAllowed, Collection $allowedAppends): static
    {
        return new static(...func_get_args());
    }
}
