<?php

namespace Spatie\QueryBuilder;

use Spatie\QueryBuilder\Sorts\Sort;
use Spatie\QueryBuilder\Sorts\SortsField;
use Spatie\QueryBuilder\Enums\SortDirection;

class AllowedSort
{
    /** @var string */
    protected $sortClass;

    /** @var \Spatie\QueryBuilder\Sorts\Sort */
    protected $name;

    /** @var string */
    protected $defaultDirection;

    /** @var string */
    protected $internalName;

    public function __construct(string $name, Sort $sortClass, ?string $internalName = null)
    {
        $this->name = ltrim($name, '-');

        $this->sortClass = $sortClass;

        $this->defaultDirection = static::parseSortDirection($name);

        $this->internalName = $internalName ?? $this->name;
    }

    public static function parseSortDirection(string $name): string
    {
        return strpos($name, '-') === 0 ? SortDirection::DESCENDING : SortDirection::ASCENDING;
    }

    public function sort(QueryBuilder $query, ?bool $descending = null): void
    {
        $descending = $descending ?? ($this->defaultDirection === SortDirection::DESCENDING);

        ($this->sortClass)($query, $descending, $this->internalName);
    }

    public static function field(string $name, ?string $internalName = null) : self
    {
        return new static($name, new SortsField, $internalName);
    }

    public static function custom(string $name, Sort $sortClass, ?string $internalName = null) : self
    {
        return new static($name, $sortClass, $internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSort(string $sortName): bool
    {
        return $this->name === $sortName;
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }
}
