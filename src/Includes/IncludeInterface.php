<?php

namespace Spatie\QueryBuilder\Includes;

use Spatie\QueryBuilder\QueryBuilder;

interface IncludeInterface
{
    public function __invoke(QueryBuilder $query, string $include);
}
