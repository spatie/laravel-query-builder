<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidFieldQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownFields;

    /** @var \Illuminate\Support\Collection */
    public $allowedFields;

    public function __construct(Collection $unknownFields, Collection $allowedFields)
    {
        $this->unknownFields = $unknownFields;
        $this->allowedFields = $allowedFields;

        $unknownFields = $unknownFields->implode(', ');
        $allowedFields = $allowedFields->implode(', ');
        $message = "Requested field(s) `{$unknownFields}` are not allowed. Allowed field(s) are `{$allowedFields}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function fieldsNotAllowed(Collection $unknownFields, Collection $allowedFields)
    {
        return new static(...func_get_args());
    }
}
