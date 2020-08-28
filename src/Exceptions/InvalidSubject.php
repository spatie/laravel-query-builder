<?php

namespace Spatie\QueryBuilder\Exceptions;

use InvalidArgumentException;

class InvalidSubject extends InvalidArgumentException
{
    public static function make($subject)
    {
        return new static(
            sprintf(
                'Subject %s is invalid.',
                is_object($subject)
                    ? sprintf('class `%s`', get_class($subject))
                    : sprintf('type `%s`', gettype($subject))
            )
        );
    }
}
