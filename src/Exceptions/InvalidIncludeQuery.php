<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidIncludeQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownIncludes;

    /** @var \Illuminate\Support\Collection */
    public $allowedIncludes;

    public function __construct(Collection $unknownIncludes, Collection $allowedIncludes)
    {
        $this->unknownIncludes = $unknownIncludes;
        $this->allowedIncludes = $allowedIncludes;

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

    public static function includesNotAllowed(Collection $unknownIncludes, Collection $allowedIncludes)
    {
        return new static(...func_get_args());
    }
}
