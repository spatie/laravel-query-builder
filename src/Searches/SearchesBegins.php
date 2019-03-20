<?php

namespace Spatie\QueryBuilder\Searches;

class SearchesBegins extends SearchesPartial
{
    protected function encloseValue($value)
    {
        return "{$value}%";
    }
}
