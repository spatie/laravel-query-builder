<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
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

    /** @var Collection */
    protected $ignored;

    public function __construct(string $property, $filterClass)
    {
        $this->property = $property;

        $this->filterClass = $filterClass;

        $this->ignored = Collection::make();
    }

    public function filter(Builder $builder, $value)
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        if (empty($valueToFilter)) {
            return;
        }

        $filterClass = $this->resolveFilterClass();

        ($filterClass)($builder, $valueToFilter, $this->property);
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

    private function resolveFilterClass(): CustomFilter
    {
        if ($this->filterClass instanceof CustomFilter) {
            return $this->filterClass;
        }

        return new $this->filterClass;
    }

    private function resolveValueForFiltering($property)
    {
        if (is_array($property)) {
            return array_diff($property, $this->ignored->toArray());
        }

        return ! $this->ignored->contains($property) ? $property : null;
    }
}
