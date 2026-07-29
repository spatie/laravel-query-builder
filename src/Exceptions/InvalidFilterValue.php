<?php

namespace Spatie\QueryBuilder\Exceptions;

use Exception;

/**
 * @consistent-constructor
 */
class InvalidFilterValue extends Exception
{
    public static function make(mixed $value): static
    {
        return new static("Filter value `{$value}` is invalid.");
    }
}
