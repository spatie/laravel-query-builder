<?php

namespace Spatie\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\SortsField;
use Spatie\QueryBuilder\Enums\SortDirection;
use Spatie\QueryBuilder\Sorts\Sort as SortClass;

class Sort
{
    /** @var string */
    protected $sortClass;

    /** @var \Spatie\QueryBuilder\Sorts\Sort */
    protected $property;

    /** @var string */
    protected $defaultDirection;

    /** @var string */
    protected $columnName;

    public function __construct(string $property, SortClass $sortClass, ?string $columnName = null)
    {
        $this->property = ltrim($property, '-');

        $this->sortClass = $sortClass;

        $this->defaultDirection = static::parsePropertyDirection($property);

        $this->columnName = $columnName ?? $this->property;
    }

    public static function parsePropertyDirection(string $property): string
    {
        return $property[0] === '-' ? SortDirection::DESCENDING : SortDirection::ASCENDING;
    }

    public function sort(Builder $builder, ?bool $descending = null)
    {
        $descending = $descending ?? ($this->defaultDirection === SortDirection::DESCENDING);

        ($this->sortClass)($builder, $descending, $this->columnName);
    }

    public static function field(string $property, ?string $columnName = null) : self
    {
        return new static($property, new SortsField, $columnName);
    }

    public static function custom(string $property, SortClass $sortClass, ?string $columnName = null) : self
    {
        return new static($property, $sortClass, $columnName);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function isForProperty(string $property): bool
    {
        return $this->property === $property;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }
}
