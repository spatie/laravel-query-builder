<?php

namespace Spatie\QueryBuilder\Exceptions;

use Illuminate\Http\Response;

class InvalidColumnName extends InvalidQuery
{
    /** @var string */
    public $column;

    public function __construct(string $column, string $message)
    {
        $this->column = $column;

        parent::__construct(Response::HTTP_BAD_REQUEST, $message);
    }

    public static function columnNameTooLong(string $column, int $maxLength = 64)
    {
        return new static($column, "Given column name `{$column}` exceeds the maximum column name length of {$maxLength} characters.");
    }

    public static function invalidCharacters(string $column)
    {
        return new static($column, 'Column name may contain only alphanumerics or underscores, and may not begin with a digit.');
    }
}
