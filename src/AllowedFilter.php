<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersBeginsWith;
use Spatie\QueryBuilder\Filters\FiltersBelongsTo;
use Spatie\QueryBuilder\Filters\FiltersCallback;
use Spatie\QueryBuilder\Filters\FiltersEndsWith;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersGroup;
use Spatie\QueryBuilder\Filters\FiltersOperator;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersTrashed;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @phpstan-import-type FiltersCallbackSchema from FiltersCallback
 */
class AllowedFilter
{
    /**
     * @var non-empty-string
     */
    protected string $internalName;

    /**
     * @var \Illuminate\Support\Collection<int,mixed>
     */
    protected Collection $ignored;

    protected mixed $default = null;

    protected bool $hasDefault = false;

    protected bool $nullable = false;

    protected ?string $arrayValueDelimiter = null;

    /**
     * @param non-empty-string $name
     * @param Filter<TModel> $filterClass
     * @param ?non-empty-string $internalName
     */
    public function __construct(
        protected string $name,
        protected Filter $filterClass,
        ?string $internalName = null,
    ) {
        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    /**
     * @param QueryBuilder<TModel> $query
     */
    public function filter(QueryBuilder $query, mixed $value): void
    {
        $this->applyTo($query->getEloquentBuilder(), $value);
    }

    /**
     * @param  Builder<TModel>  $builder
     */
    public function applyTo(Builder $builder, mixed $value): void
    {
        $value = $this->splitFilterValue($value);

        $valueToFilter = $this->resolveValueForFiltering($value);

        if (! $this->nullable && is_null($valueToFilter)) {
            return;
        }

        ($this->filterClass)($builder, $valueToFilter, $this->internalName);
    }

    public function delimiter(string $delimiter): static
    {
        $this->arrayValueDelimiter = $delimiter;

        return $this;
    }

    public function getDelimiter(): string
    {
        return $this->arrayValueDelimiter ?? config()->string('query-builder.delimiter', ',');
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function exact(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        /** @var FiltersExact<TModel> */
        $filter = new FiltersExact($addRelationConstraint);

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function partial(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        /** @var FiltersPartial<TModel> */
        $filter = new FiltersPartial($addRelationConstraint);

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function beginsWith(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        /** @var FiltersBeginsWith<TModel> */
        $filter = new FiltersBeginsWith($addRelationConstraint);

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function endsWith(string $name, ?string $internalName = null, bool $addRelationConstraint = true): static
    {
        /** @var FiltersEndsWith<TModel> */
        $filter = new FiltersEndsWith($addRelationConstraint);

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function belongsTo(string $name, ?string $internalName = null): static
    {
        /** @var FiltersEndsWith<TModel> */
        $filter = new FiltersBelongsTo;

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function scope(string $name, ?string $internalName = null): static
    {
        /** @var FiltersScope<TModel> */
        $filter = new FiltersScope;

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param FiltersCallbackSchema $callback
     * @param ?non-empty-string $internalName
     */
    public static function callback(string $name, callable $callback, ?string $internalName = null): static
    {
        /** @var FiltersCallback<TModel> */
        $filter = new FiltersCallback($callback);

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param ?non-empty-string $internalName
     */
    public static function trashed(string $name = 'trashed', ?string $internalName = null): static
    {
        /** @var FiltersTrashed<TModel> */
        $filter = new FiltersTrashed;

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param Filter<TModel> $filterClass
     * @param ?non-empty-string $internalName
     */
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
        /** @var FiltersOperator<TModel> */
        $filter = new FiltersOperator($addRelationConstraint, $filterOperator, $boolean);

        return new static($name, $filter, $internalName);
    }

    /**
     * @param non-empty-string $name
     * @param  AllowedFilter<TModel>[]  $members
     */
    public static function groupOr(string $name, array $members): static
    {
        /** @var FiltersGroup<TModel> */
        $filter = new FiltersGroup('or', $members);

        return new static($name, $filter);
    }

    /**
     * @param non-empty-string $name
     * @param  AllowedFilter<TModel>[]  $members
     */
    public static function groupAnd(string $name, array $members): static
    {
        /** @var FiltersGroup<TModel> */
        $filter = new FiltersGroup('and', $members);

        return new static($name, $filter);
    }

    /**
     * @return Filter<TModel>
     */
    public function getFilterClass(): Filter
    {
        return $this->filterClass;
    }

    /**
     * @return non-empty-string
     */
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

    /**
     * @return array<array-key, mixed>
     */
    public function getIgnored(): array
    {
        return $this->ignored->all();
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
        $this->default = null;

        return $this;
    }

    protected function splitFilterValue(mixed $value): mixed
    {
        if ($this->filterValueSplittingDisabled()) {
            return $value;
        }

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
            return array_map([$this, 'resolveValueForFiltering'], $value) ?: null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }

    protected function filterValueSplittingDisabled(): bool
    {
        return null === $this->arrayValueDelimiter
            && ! config()->boolean('query-builder.filter_value_splitting_enabled', true);
    }
}
