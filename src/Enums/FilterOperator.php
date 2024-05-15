<?php

namespace Spatie\QueryBuilder\Enums;

enum FilterOperator: string
{
    case DYNAMIC = '';
    case EQUAL = '=';
    case LESS_THAN = '<';
    case GREATER_THAN = '>';
    case LESS_THAN_OR_EQUAL = '<=';
    case GREATER_THAN_OR_EQUAL = '>=';
    case NOT_EQUAL = '<>';

    public function isDynamic()
    {
        return self::DYNAMIC === $this;
    }
}
