<?php

namespace Spatie\QueryBuilder\Searches;

class SearchesSplitBegins extends SearchesSplit
{
    protected function encloseValue($value)
    {
        return "{$value}%";
    }
}
