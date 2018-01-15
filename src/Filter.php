<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersPartial;

class Filter
{
    /** @var string */
    protected $filterClass;

    /** @var string */
    protected $property;

    public function __construct(string $property, string $filterClass)
    {
        $this->property = $property;
        $this->filterClass = $filterClass;
    }

    public function filter(Builder $builder, $value)
    {
        $filterClass = new $this->filterClass;

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

    public static function custom(string $property, string $filterClass) : self
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
}
