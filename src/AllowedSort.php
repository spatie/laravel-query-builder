<?php

namespace Spatie\QueryBuilder;

use Spatie\QueryBuilder\Enums\SortDirection;
use Spatie\QueryBuilder\Sorts\Sort;
use Spatie\QueryBuilder\Sorts\SortsCallback;
use Spatie\QueryBuilder\Sorts\SortsField;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @phpstan-import-type SortsCallbackSchema from SortsCallback
 */
class AllowedSort
{
    protected SortDirection $defaultDirection;

    /**
     * @var non-empty-string
     */
    protected string $internalName;

    /**
     * @param non-empty-string $name
     * @param Sort<TModel> $sortClass
     * @param ?non-empty-string $internalName
     */
    public function __construct(
        protected string $name,
        protected Sort $sortClass,
        ?string $internalName = null,
    ) {
        $this->name = ltrim($name, '-');

        $this->defaultDirection = static::parseSortDirection($name);

        $this->internalName = $internalName ?? $this->name;
    }

    /**
     * @param non-empty-string $name
     */
    public static function parseSortDirection(string $name): SortDirection
    {
        return str_starts_with($name, '-') ? SortDirection::Descending : SortDirection::Ascending;
    }

    /**
     * @param QueryBuilder<TModel> $query
     */
    public function sort(QueryBuilder $query, ?bool $descending = null): void
    {
        $descending = $descending ?? ($this->defaultDirection === SortDirection::Descending);

        /** @var \Illuminate\Database\Eloquent\Builder<TModel> */
        $builder = $query->getEloquentBuilder();

        ($this->sortClass)($builder, $descending, $this->internalName);
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function field(string $name, ?string $internalName = null): static
    {
        /** @var SortsField<TModel> */
        $sorter = new SortsField;

        return new static($name, $sorter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param Sort<TModel> $sortClass
     * @param ?non-empty-string $internalName
     */
    public static function custom(string $name, Sort $sortClass, ?string $internalName = null): static
    {
        return new static($name, $sortClass, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param SortsCallbackSchema $callback
     * @param ?non-empty-string $internalName
     */
    public static function callback(string $name, callable $callback, ?string $internalName = null): static
    {
        /** @var SortsCallback<TModel> */
        $sorter = new SortsCallback($callback);

        return new static($name, $sorter, $internalName);
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function isSort(string $sortName): bool
    {
        return $this->name === $sortName;
    }

    /**
     * @return non-empty-string
     */
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
