<?php

namespace Spatie\QueryBuilder\Searches;

class SearchesSplitEnds extends SearchesSplit
{
    protected function encloseValue($value)
    {
        return "%{$value}";
    }
}
