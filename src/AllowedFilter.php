<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersPartial;

class AllowedFilter
{
    /** @var \Spatie\QueryBuilder\Filters\Filter */
    protected $filterClass;

    /** @var string */
    protected $property;

    /** @var string */
    protected $columnName;

    /** @var Collection */
    protected $ignored;

    public function __construct(string $property, Filter $filterClass, ?string $columnName = null)
    {
        $this->property = $property;

        $this->filterClass = $filterClass;

        $this->ignored = Collection::make();

        $this->columnName = $columnName ?? $property;
    }

    public function filter(QueryBuilder $query, $value)
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        if (is_null($valueToFilter)) {
            return;
        }

        ($this->filterClass)($query, $valueToFilter, $this->columnName);
    }

    public static function exact(string $property, ?string $columnName = null) : self
    {
        return new static($property, new FiltersExact(), $columnName);
    }

    public static function partial(string $property, $columnName = null) : self
    {
        return new static($property, new FiltersPartial(), $columnName);
    }

    public static function scope(string $property, $columnName = null) : self
    {
        return new static($property, new FiltersScope(), $columnName);
    }

    public static function custom(string $property, Filter $filterClass, $columnName = null) : self
    {
        return new static($property, $filterClass, $columnName);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function isForProperty(string $property): bool
    {
        return $this->property === $property;
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

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    protected function resolveValueForFiltering($property)
    {
        if (is_array($property)) {
            $remainingProperties = array_diff($property, $this->ignored->toArray());

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($property) ? $property : null;
    }
}
