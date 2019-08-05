<?php

namespace Spatie\QueryBuilder\Exceptions;

use BadMethodCallException;

class AllowedFieldsMustBeCalledBeforeAllowedIncludes extends BadMethodCallException
{
    public function __construct()
    {
        parent::__construct("The QueryBuilder's `allowedFields` method must be called before the `allowedIncludes` method.");
    }
}
