<?php

namespace Spatie\QueryBuilder;

use Spatie\QueryBuilder\Enums\SortDirection;
use Spatie\QueryBuilder\Sorts\Sort;
use Spatie\QueryBuilder\Sorts\SortsCallback;
use Spatie\QueryBuilder\Sorts\SortsField;

class AllowedSort
{
    protected SortDirection $defaultDirection;

    protected string $internalName;

    public function __construct(
        protected string $name,
        protected Sort $sortClass,
        ?string $internalName = null,
    ) {
        $this->name = ltrim($name, '-');

        $this->defaultDirection = static::parseSortDirection($name);

        $this->internalName = $internalName ?? $this->name;
    }

    public static function parseSortDirection(string $name): SortDirection
    {
        return str_starts_with($name, '-') ? SortDirection::Descending : SortDirection::Ascending;
    }

    public function sort(QueryBuilder $query, ?bool $descending = null): void
    {
        $descending = $descending ?? ($this->defaultDirection === SortDirection::Descending);

        ($this->sortClass)($query->getEloquentBuilder(), $descending, $this->internalName);
    }

    public static function field(string $name, ?string $internalName = null): static
    {
        return new static($name, new SortsField(), $internalName);
    }

    public static function custom(string $name, Sort $sortClass, ?string $internalName = null): static
    {
        return new static($name, $sortClass, $internalName);
    }

    public static function callback(string $name, callable $callback, ?string $internalName = null): static
    {
        return new static($name, new SortsCallback($callback), $internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSort(string $sortName): bool
    {
        return $this->name === $sortName;
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }

    public function defaultDirection(SortDirection $defaultDirection): static
    {
        $this->defaultDirection = $defaultDirection;

        return $this;
    }
}
