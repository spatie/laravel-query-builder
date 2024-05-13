<?php

namespace Spatie\QueryBuilder\Mappings;

class Column
{
    /**
     * Create a new currency instance.
     *
     * @param  string  $name
     * @return void
     */
    function __construct(string $name)
    {
        $this->name = $name;
    }

}
