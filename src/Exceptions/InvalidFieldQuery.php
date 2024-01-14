<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidFieldQuery extends InvalidQuery
{
    public function __construct(
        public Collection $unknownFields,
        public Collection $allowedFields
    ) {
        $unknownFields = $unknownFields->implode(', ');
        $allowedFields = $allowedFields->implode(', ');
        $message = "Requested field(s) `{$unknownFields}` are not allowed. Allowed field(s) are `{$allowedFields}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function fieldsNotAllowed(Collection $unknownFields, Collection $allowedFields): static
    {
        return new static(...func_get_args());
    }
}
