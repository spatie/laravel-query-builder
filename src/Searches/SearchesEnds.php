<?php

namespace Spatie\QueryBuilder\Searches;

class SearchesEnds extends SearchesPartial
{
    protected function encloseValue($value)
    {
        return "%{$value}";
    }
}
