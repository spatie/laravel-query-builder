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
    protected $columnName;

    public function __construct(string $property, $filterClass, $columnName = null)
    {
        $this->property = $property;

        $this->filterClass = $filterClass;

        $this->columnName = $columnName ?? $property;
    }

    public function filter(Builder $builder, $value)
    {
        $filterClass = $this->resolveFilterClass();

        ($filterClass)($builder, $value, $this->columnName);
    }

    public static function exact(string $property, $columnName = null) : self
    {
        return new static($property, FiltersExact::class, $columnName);
    }

    public static function partial(string $property, $columnName = null) : self
    {
        return new static($property, FiltersPartial::class, $columnName);
    }

    public static function scope(string $property, $columnName = null) : self
    {
        return new static($property, FiltersScope::class, $columnName);
    }

    public static function custom(string $property, $filterClass, $columnName = null) : self
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

    public function getcolumnName(): string
    {
        return $this->columnName;
    }

    private function resolveFilterClass(): CustomFilter
    {
        if ($this->filterClass instanceof CustomFilter) {
            return $this->filterClass;
        }

        return new $this->filterClass;
    }
}
