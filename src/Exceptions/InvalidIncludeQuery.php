<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidIncludeQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    private $unknownIncludes;

    /** @var \Illuminate\Support\Collection */
    private $allowedIncludes;

    public function __construct(Collection $unknownIncludes, Collection $allowedIncludes)
    {
        $this->unknownIncludes = $unknownIncludes;
        $this->allowedIncludes = $allowedIncludes;

        $unknownIncludes = $unknownIncludes->implode(', ');
        $allowedIncludes = $allowedIncludes->implode(', ');
        $message = "Given include(s) `{$unknownIncludes}` are not allowed. Allowed includes are `{$allowedIncludes}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function includesNotAllowed(Collection $unknownIncludes, Collection $allowedIncludes)
    {
        return new static(...func_get_args());
    }

    public function getUnknownIncludes(): Collection
    {
        return $this->unknownIncludes;
    }

    public function getAllowedIncludes(): Collection
    {
        return $this->allowedIncludes;
    }
}
