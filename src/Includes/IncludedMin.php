<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class IncludedMin implements IncludeInterface
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

        $query->withMin($relation, $this->column);
    }
}
