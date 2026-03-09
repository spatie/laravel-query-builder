<?php

namespace Spatie\QueryBuilder\Exceptions;

use Exception;
use Spatie\QueryBuilder\Enums\SortDirection;

class InvalidDirection extends Exception
{
    public static function make(string $sort): static
    {
        return new static('The direction should be either `'.SortDirection::Descending->value.'` or `'.SortDirection::Ascending->value."`. {$sort} given.");
    }
}
