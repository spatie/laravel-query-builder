<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\SortsField;
use Spatie\QueryBuilder\Enums\SortDirection;
use Spatie\QueryBuilder\Sorts\Sort as CustomSort;

class Sort
{
    /** @var string */
    protected $sortClass;

    /** @var string */
    protected $property;

    /** @var string */
    protected $defaultDirection;

    public function __construct(string $property, $sortClass)
    {
        $this->property = ltrim($property, '-');
        $this->sortClass = $sortClass;
        $this->defaultDirection = static::parsePropertyDirection($property);
    }

    public static function parsePropertyDirection(string $property): string
    {
        return $property[0] === '-' ? SortDirection::DESCENDING : SortDirection::ASCENDING;
    }

    public function sort(Builder $builder, ?bool $descending = null)
    {
        $sortClass = $this->resolveSortClass();

        $descending = $descending ?? ($this->defaultDirection === SortDirection::DESCENDING);

        ($sortClass)($builder, $descending, $this->property);
    }

    public static function field(string $property) : self
    {
        return new static($property, SortsField::class);
    }

    public static function custom(string $property, $sortClass) : self
    {
        return new static($property, $sortClass);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function isForProperty(string $property): bool
    {
        return $this->property === $property;
    }

    private function resolveSortClass(): CustomSort
    {
        if ($this->sortClass instanceof CustomSort) {
            return $this->sortClass;
        }

        return new $this->sortClass;
    }
}
