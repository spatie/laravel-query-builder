<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidIncludeQuery extends InvalidQuery
{
    public function __construct(
        public Collection $unknownIncludes,
        public Collection $allowedIncludes
    ) {
        $unknownIncludes = $unknownIncludes->implode(', ');

        $message = "Requested include(s) `{$unknownIncludes}` are not allowed. ";

        if ($allowedIncludes->count()) {
            $allowedIncludes = $allowedIncludes->implode(', ');
            $message .= "Allowed include(s) are `{$allowedIncludes}`.";
        } else {
            $message .= 'There are no allowed includes.';
        }

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function includesNotAllowed(Collection $unknownIncludes, Collection $allowedIncludes): static
    {
        return new static(...func_get_args());
    }
}
