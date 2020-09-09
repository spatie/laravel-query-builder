<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersCallback;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersTrashed;

class AllowedFilter
{
    /** @var \Spatie\QueryBuilder\Filters\Filter */
    protected $filterClass;

    /** @var string */
    protected $name;

    /** @var string */
    protected $internalName;

    /** @var \Illuminate\Support\Collection */
    protected $ignored;

    /** @var mixed */
    protected $default;

    public function __construct(string $name, Filter $filterClass, ?string $internalName = null)
    {
        $this->name = $name;

        $this->filterClass = $filterClass;

        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    public function filter(QueryBuilder $query, $value)
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        if (is_null($valueToFilter)) {
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

    public static function exact(string $name, ?string $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersExact($addRelationConstraint), $internalName);
    }

    public static function partial(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersPartial($addRelationConstraint), $internalName);
    }

    public static function scope(string $name, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersScope(), $internalName);
    }

    public static function callback(string $name, $callback, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersCallback($callback), $internalName);
    }

    public static function trashed(string $name = 'trashed', $internalName = null): self
    {
        return new static($name, new FiltersTrashed(), $internalName);
    }

    public static function custom(string $name, Filter $filterClass, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, $filterClass, $internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForFilter(string $filterName): bool
    {
        return $this->name === $filterName;
    }

    public function ignore(...$values): self
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

    public function default($value): self
    {
        $this->default = $value;

        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function hasDefault(): bool
    {
        return isset($this->default);
    }

    protected function resolveValueForFiltering($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_diff_assoc($value, $this->ignored->toArray());

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }
}
