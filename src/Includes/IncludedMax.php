<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;

class IncludedMax implements IncludeInterface
{
    public function __construct(
        protected string $relation,
        protected string $column,
    ) {}

    public function __invoke(Builder $query, string $include): void
    {
        $query->withMax($this->relation, $this->column);
    }
}
