<?php

namespace Spatie\QueryBuilder\Exceptions;

use RuntimeException;

class WildcardNotAllowedInEnvironment extends RuntimeException
{
    public static function create(string $environment): static
    {
        return new static("Wildcard `*` is not allowed in the `{$environment}` environment. This feature should only be used in local or testing environments.");
    }
}
