<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;

class UnknownIncludedFieldsQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownFields;

    public function __construct(array $unknownFields)
    {
        $this->unknownFields = collect($unknownFields);

        $unknownFields = $this->unknownFields->implode(', ');

        $message = "Requested field(s) `{$unknownFields}` are not allowed (yet). ";
        $message .= "If you want to allow these fields, please make sure to call the QueryBuilder's `allowedFields` method before the `allowedIncludes` method.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }
}
