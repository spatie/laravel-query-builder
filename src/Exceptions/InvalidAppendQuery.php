<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class InvalidAppendQuery extends InvalidQuery
{
    /** @var \Illuminate\Support\Collection */
    public $unknownAppends;

    /** @var \Illuminate\Support\Collection */
    public $allowedAppends;

    public function __construct(Collection $unknownAppends, Collection $allowedAppends)
    {
        $this->unknownAppends = $unknownAppends;
        $this->allowedAppends = $allowedAppends;

        $unknownAppends = $unknownAppends->implode(', ');
        $allowedAppends = $allowedAppends->implode(', ');
        $message = "Given append(s) `{$unknownAppends}` are not allowed. Allowed append(s) are `{$allowedAppends}`.";

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function appendsNotAllowed(Collection $unknownAppends, Collection $allowedAppends)
    {
        return new static(...func_get_args());
    }
}
