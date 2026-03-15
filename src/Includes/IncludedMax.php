<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class IncludedMax implements IncludeInterface
{
    public function __construct(
        protected string $relation,
        protected string $column,
        protected ?Closure $constraint = null,
    ) {
    }

    public function __invoke(Builder $query, string $include): void
    {
        $relation = $this->constraint ? [$this->relation => $this->constraint] : $this->relation;

        $query->withMax($relation, $this->column);
    }
}
