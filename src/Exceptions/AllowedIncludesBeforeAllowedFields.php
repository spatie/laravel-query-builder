<?php

namespace Spatie\QueryBuilder\Exceptions;

use BadMethodCallException;

class AllowedIncludesBeforeAllowedFields extends BadMethodCallException
{
    public function __construct()
    {
        parent::__construct("The QueryBuilder's `allowedFields` method should be called before the `allowedIncludes` method.");
    }
}
