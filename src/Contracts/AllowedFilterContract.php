<?php

namespace Spatie\QueryBuilder\Contracts;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;

interface AllowedFilterContract
{
    public function isRequested(QueryBuilderRequest $request): bool;

    public function getValueFromRequest(QueryBuilderRequest $request): mixed;

    public function getValueFromCollection(Collection $value): mixed;

    public function filter(QueryBuilder $query, $value);

    public function hasDefault(): bool;

    public function getDefault(): mixed;

    public function getNames(): array;
}
