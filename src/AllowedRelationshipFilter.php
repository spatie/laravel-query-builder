<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Contracts\AllowedFilterContract;

class AllowedRelationshipFilter implements AllowedFilterContract
{
    /** @var string */
    protected $relationship;

    /** @var \Illuminate\Support\Collection */
    protected $allowedFilters;

    public function __construct(string $relationship, AllowedFilterContract ...$allowedFilters)
    {
        $this->relationship = $relationship;

        $this->allowedFilters = collect($allowedFilters);
    }

    public static function group(string $relationship, AllowedFilterContract ...$allowedFilters): self
    {
        return new static($relationship, ...$allowedFilters);
    }

    public function filter(QueryBuilder $query, $value)
    {
        $query->whereHas($this->relationship, function ($query) use ($value) {
            $this->allowedFilters->each(
                function (AllowedFilterContract $allowedFilter) use ($query, $value) {
                    $allowedFilter->filter(
                        QueryBuilder::for($query),
                        $allowedFilter->getValueFromCollection($value)
                    );
                }
            );
        });
    }

    public function getNames(): array
    {
        return $this->allowedFilters->map(
            fn (AllowedFilterContract $allowedFilter) => $allowedFilter->getNames()
        )->flatten()->toArray();
    }

    public function isRequested(QueryBuilderRequest $request): bool
    {
        return $request->filters()->hasAny($this->getNames());
    }

    public function getValueFromRequest(QueryBuilderRequest $request): Collection
    {
        return $request->filters()->only($this->getNames());
    }

    public function getValueFromCollection(Collection $value): Collection
    {
        return $value->only($this->getNames());
    }

    public function hasDefault(): bool
    {
        return false;
    }

    public function getDefault(): mixed
    {
        return null;
    }
}
