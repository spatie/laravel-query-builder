<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;

class IncludedAvg implements IncludeInterface
{
    public function __construct(
        protected string $relation,
        protected string $column,
    ) {}

    public function __invoke(Builder $query, string $include): void
    {
        $query->withAvg($this->relation, $this->column);
    }
}
