<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;

class IncludedSum implements IncludeInterface
{
    public function __construct(
        protected string $relation,
        protected string $column,
    ) {
    }

    public function __invoke(Builder $query, string $include): void
    {
        $query->withSum($this->relation, $this->column);
    }
}
