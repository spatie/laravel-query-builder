<?php

namespace Spatie\QueryBuilder\Enums;

enum FilterOperator: string
{
    case EQUAL = '=';
    case NOT_EQUAL = '<>';
    case LESS_THAN = '<';
    case GREATER_THAN = '>';
    case LESS_THAN_OR_EQUAL = '<=';
    case GREATER_THAN_OR_EQUAL = '>=';
}
