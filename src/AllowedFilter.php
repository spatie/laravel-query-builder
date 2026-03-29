<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersBeginsWith;
use Spatie\QueryBuilder\Filters\FiltersBelongsTo;
use Spatie\QueryBuilder\Filters\FiltersCallback;
use Spatie\QueryBuilder\Filters\FiltersEndsWith;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersOperator;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersTrashed;

class AllowedFilter
{
    protected string $internalName;

    protected Collection $ignored;

    protected mixed $default = null;

    protected bool $hasDefault = false;

    protected bool $nullable = false;

    protected ?string $arrayValueDelimiter = null;

    public function __construct(
        protected string $name,
        protected Filter $filterClass,
        ?string $internalName = null,
    ) {
        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    public function filter(QueryBuilder $query, mixed $value): void
    {
        $value = $this->splitFilterValue($value);

        $valueToFilter = $this->resolveValueForFiltering($value);

        if (! $this->nullable && is_null($valueToFilter)) {
            return;
        }

        ($this->filterClass)($query->getEloquentBuilder(), $valueToFilter, $this->internalName);
    }

    public function delimiter(string $delimiter): static
    {
        $this->arrayValueDelimiter = $delimiter;

        return $this;
    }

    public function getDelimiter(): string
    {
        return $this->arrayValueDelimiter ?? config('query-builder.delimiter', ',');
    }

    public static function exact(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        return new static($name, new FiltersExact($addRelationConstraint), $internalName);
    }

    public static function partial(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        return new static($name, new FiltersPartial($addRelationConstraint), $internalName);
    }

    public static function beginsWith(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        return new static($name, new FiltersBeginsWith($addRelationConstraint), $internalName);
    }

    public static function endsWith(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        return new static($name, new FiltersEndsWith($addRelationConstraint), $internalName);
    }

    public static function belongsTo(string $name, ?string $internalName = null): static
    {
        return new static($name, new FiltersBelongsTo(), $internalName);
    }

    public static function scope(string $name, ?string $internalName = null): static
    {
        return new static($name, new FiltersScope(), $internalName);
    }

    public static function callback(string $name, callable $callback, ?string $internalName = null): static
    {
        return new static($name, new FiltersCallback($callback), $internalName);
    }

    public static function trashed(string $name = 'trashed', ?string $internalName = null): static
    {
        return new static($name, new FiltersTrashed(), $internalName);
    }

    public static function custom(string $name, Filter $filterClass, ?string $internalName = null): static
    {
        return new static($name, $filterClass, $internalName);
    }

    public static function operator(
        string $name,
        FilterOperator $filterOperator,
        string $boolean = 'and',
        ?string $internalName = null,
        bool $addRelationConstraint = true,
    ): static {
        return new static($name, new FiltersOperator($addRelationConstraint, $filterOperator, $boolean), $internalName);
    }

    public function getFilterClass(): Filter
    {
        return $this->filterClass;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForFilter(string $filterName): bool
    {
        return $this->name === $filterName;
    }

    public function ignore(mixed ...$values): static
    {
        $this->ignored = $this->ignored
            ->merge($values)
            ->flatten();

        return $this;
    }

    public function getIgnored(): array
    {
        return $this->ignored->toArray();
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }

    public function default(mixed $value): static
    {
        $this->hasDefault = true;
        $this->default = $value;

        if (is_null($value)) {
            $this->nullable(true);
        }

        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function unsetDefault(): static
    {
        $this->hasDefault = false;
        unset($this->default);

        return $this;
    }

    protected function splitFilterValue(mixed $value): mixed
    {
        $delimiter = $this->getDelimiter();

        if ($delimiter === '') {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->splitFilterValue($v), $value);
        }

        if (is_string($value) && Str::contains($value, $delimiter)) {
            return explode($delimiter, $value);
        }

        return $value;
    }

    protected function resolveValueForFiltering(mixed $value): mixed
    {
        if (is_array($value)) {
            $remainingProperties = array_map([$this, 'resolveValueForFiltering'], $value);

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }
}
