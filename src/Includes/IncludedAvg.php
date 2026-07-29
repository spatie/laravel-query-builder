<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements IncludeInterface<TModel>
 */
class IncludedAvg implements IncludeInterface
{
    public function __construct(
        protected string $relation,
        protected string $column,
        protected ?Closure $constraint = null,
    ) {}

    public function __invoke(Builder $query, string $include): void
    {
        $relation = $this->constraint ? [$this->relation => $this->constraint] : $this->relation;

        $query->withAvg($relation, $this->column);
    }
}
