<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\Filter as CustomFilter;

class Filter
{
    /** @var string */
    protected $filterClass;

    /** @var string */
    protected $property;

    /** @var string */
    protected $propertyColumnName;

    public function __construct(string $property, $filterClass, $propertyColumnName = null)
    {
        $this->property = $property;

        $this->filterClass = $filterClass;

        $this->propertyColumnName = $propertyColumnName ?? $property;
    }

    public function filter(Builder $builder, $value)
    {
        $filterClass = $this->resolveFilterClass();

        ($filterClass)($builder, $value, $this->propertyColumnName);
    }

    public static function exact(string $property, $propertyColumnName = null) : self
    {
        return new static($property, FiltersExact::class, $propertyColumnName);
    }

    public static function partial(string $property, $propertyColumnName = null) : self
    {
        return new static($property, FiltersPartial::class, $propertyColumnName);
    }

    public static function scope(string $property, $propertyColumnName = null) : self
    {
        return new static($property, FiltersScope::class, $propertyColumnName);
    }

    public static function custom(string $property, $filterClass, $propertyColumnName = null) : self
    {
        return new static($property, $filterClass, $propertyColumnName);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function isForProperty(string $property): bool
    {
        return $this->property === $property;
    }

    public function getPropertyColumnName(): string
    {
        return $this->propertyColumnName;
    }

    private function resolveFilterClass(): CustomFilter
    {
        if ($this->filterClass instanceof CustomFilter) {
            return $this->filterClass;
        }

        return new $this->filterClass;
    }
}
