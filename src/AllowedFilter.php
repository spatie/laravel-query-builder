<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersBeginsWithStrict;
use Spatie\QueryBuilder\Filters\FiltersCallback;
use Spatie\QueryBuilder\Filters\FiltersEndsWithStrict;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersOperator;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersTrashed;

class AllowedFilter
{
    protected string $internalName;

    protected Collection $ignored;

    protected mixed $default;

    protected bool $hasDefault = false;

    protected bool $nullable = false;

    public function __construct(
        protected string $name,
        protected Filter $filterClass,
        ?string $internalName = null
    ) {
        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    public function filter(QueryBuilder $query, $value): void
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        if (! $this->nullable && is_null($valueToFilter)) {
            return;
        }

        ($this->filterClass)($query->getEloquentBuilder(), $valueToFilter, $this->internalName);
    }

    public static function setFilterArrayValueDelimiter(string $delimiter = null): void
    {
        if (isset($delimiter)) {
            QueryBuilderRequest::setFilterArrayValueDelimiter($delimiter);
        }
    }

    public static function exact(string $name, ?string $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): static
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersExact($addRelationConstraint), $internalName);
    }

    public static function partial(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): static
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersPartial($addRelationConstraint), $internalName);
    }

    public static function beginsWithStrict(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): static
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersBeginsWithStrict($addRelationConstraint), $internalName);
    }

    public static function endsWithStrict(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): static
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersEndsWithStrict($addRelationConstraint), $internalName);
    }

    public static function scope(string $name, $internalName = null, string $arrayValueDelimiter = null): static
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersScope(), $internalName);
    }

    public static function callback(string $name, $callback, $internalName = null, string $arrayValueDelimiter = null): static
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersCallback($callback), $internalName);
    }

    public static function trashed(string $name = 'trashed', $internalName = null): static
    {
        return new static($name, new FiltersTrashed(), $internalName);
    }

    public static function custom(string $name, Filter $filterClass, $internalName = null, string $arrayValueDelimiter = null): static
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, $filterClass, $internalName);
    }

    public static function operator(string $name, FilterOperator $filterOperator, string $boolean = 'and', ?string $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersOperator($addRelationConstraint, $filterOperator, $boolean), $internalName, $filterOperator);
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

    public function ignore(...$values): static
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

    public function default($value): static
    {
        $this->hasDefault = true;
        $this->default = $value;

        if (is_null($value)) {
            $this->nullable(true);
        }

        return $this;
    }

    public function getDefault()
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

    protected function resolveValueForFiltering($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_map([$this, 'resolveValueForFiltering'], $value);

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }
}
