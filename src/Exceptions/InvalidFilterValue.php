<?php

namespace Spatie\QueryBuilder\Exceptions;

use Exception;

class InvalidFilterValue extends Exception
{
    public static function make($value): static
    {
        return new static("Filter value `{$value}` is invalid.");
    }
}
