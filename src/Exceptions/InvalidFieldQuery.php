<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * @consistent-constructor
 */
class InvalidFieldQuery extends InvalidQuery
{
    /**
     * @param  Collection<array-key, mixed>  $unknownFields
     * @param  Collection<array-key, mixed>  $allowedFields
     */
    public function __construct(
        public Collection $unknownFields,
        public Collection $allowedFields
    ) {
        $unknownFields = $unknownFields->implode(', ');
        $allowedFields = $allowedFields->implode(', ');
        $message = "Requested field(s) `{$unknownFields}` are not allowed. Allowed field(s) are `{$allowedFields}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    /**
     * @param  Collection<array-key, mixed>  $unknownFields
     * @param  Collection<array-key, mixed>  $allowedFields
     */
    public static function fieldsNotAllowed(Collection $unknownFields, Collection $allowedFields): static
    {
        return new static(...func_get_args());
    }
}
