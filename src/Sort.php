<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\SortsField;
use Spatie\QueryBuilder\Sorts\Sort as CustomSort;

class Sort
{
    /** @var string */
    protected $sortClass;

    /** @var string */
    protected $property;

    public function __construct(string $property, $sortClass)
    {
        $this->property = $property;
        $this->sortClass = $sortClass;
    }

    public function sort(Builder $builder, $descending)
    {
        $sortClass = $this->resolveSortClass();

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
