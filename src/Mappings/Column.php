<?php

namespace Spatie\QueryBuilder\Mappings;

class Column
{
    /**
     * Create a new currency instance.
     *
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
