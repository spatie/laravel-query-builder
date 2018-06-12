<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter as CustomFilter;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersPartial;

class Filter
{
    /** @var string */
    protected $filterClass;

    /** @var string */
    protected $property;

    public function __construct(string $property, $filterClass)
    {
        $this->property = $property;
        $this->filterClass = $filterClass;
    }

    public function filter(Builder $builder, $value)
    {
        $filterClass = $this->resolveFilterClass();

        ($filterClass)($builder, $value, $this->property);
    }

    public static function exact(string $property) : self
    {
        return new static($property, FiltersExact::class);
    }

    public static function partial(string $property) : self
    {
        return new static($property, FiltersPartial::class);
    }

    public static function scope(string $property) : self
    {
        return new static($property, FiltersScope::class);
    }

    public static function custom(string $property, $filterClass) : self
    {
        return new static($property, $filterClass);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function isForProperty(string $property): bool
    {
        return $this->property === $property;
    }

    private function resolveFilterClass(): CustomFilter
    {
        if ($this->filterClass instanceof CustomFilter) {
            return $this->filterClass;
        }

        return new $this->filterClass;
    }
}
