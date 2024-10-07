<?php

namespace Spatie\QueryBuilder\Exceptions;

use Exception;

class InvalidFilterProperty extends Exception
{
    public static function make($property): static
    {
        return new static("Filter property `{$property}` is invalid.");
    }
}
