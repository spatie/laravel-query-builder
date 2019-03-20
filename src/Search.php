<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

use Spatie\QueryBuilder\Searches\Search as CustomSearch;
use Spatie\QueryBuilder\Searches\SearchesBegins;
use Spatie\QueryBuilder\Searches\SearchesEnds;
use Spatie\QueryBuilder\Searches\SearchesExact;
use Spatie\QueryBuilder\Searches\SearchesPartial;
use Spatie\QueryBuilder\Searches\SearchesModifierResolver;
use Spatie\QueryBuilder\Searches\SearchesSplit;
use Spatie\QueryBuilder\Searches\SearchesSplitBegins;
use Spatie\QueryBuilder\Searches\SearchesSplitEnds;

class Search
{
    /** @var string|\Spatie\QueryBuilder\Searches\Search */
    protected $searchClass;

    /** @var string */
    protected $property;

    /** @var string */
    protected $columnName;

    /** @var Collection */
    protected $ignored;

    public function __construct(string $property, $searchClass, ?string $columnName = null)
    {
        $this->property = $property;

        $this->searchClass = $searchClass;

        $this->ignored = Collection::make();

        $this->columnName = $columnName ?? $property;
    }

    public function search(Builder $builder, $value, $modifier = null)
    {
        $valueToSearch = $this->resolveValueForSearching($value);

        if (is_null($valueToSearch)) {
            return;
        }

        $searchClass = $this->resolveSearchClass();

        ($searchClass)($builder, $valueToSearch, $this->columnName, $modifier);
    }

    public static function begins(string $property, ?string $columnName = null): self
    {
        return new static($property, SearchesBegins::class, $columnName);
    }

    public static function ends(string $property, ?string $columnName = null): self
    {
        return new static($property, SearchesEnds::class, $columnName);
    }

    public static function exact(string $property, ?string $columnName = null): self
    {
        return new static($property, SearchesExact::class, $columnName);
    }

    public static function partial(string $property, $columnName = null): self
    {
        return new static($property, SearchesPartial::class, $columnName);
    }

    public static function split(string $property, $columnName = null): self
    {
        return new static($property, SearchesSplit::class, $columnName);
    }

    public static function splitBegins(string $property, ?string $columnName = null): self
    {
        return new static($property, SearchesSplitBegins::class, $columnName);
    }

    public static function splitEnds(string $property, ?string $columnName = null): self
    {
        return new static($property, SearchesSplitEnds::class, $columnName);
    }

    public static function resolver(string $property, $columnName = null): self
    {
        return new static($property, SearchesModifierResolver::class, $columnName);
    }

    public static function custom(string $property, $searchClass, $columnName = null): self
    {
        return new static($property, $searchClass, $columnName);
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

    private function resolveSearchClass(): CustomSearch
    {
        if ($this->searchClass instanceof CustomSearch) {
            return $this->searchClass;
        }

        return new $this->searchClass;
    }

    private function resolveValueForSearching($property)
    {
        if (is_array($property)) {
            $remainingProperties = array_diff($property, $this->ignored->toArray());

            return !empty($remainingProperties) ? $remainingProperties : null;
        }

        return !$this->ignored->contains($property) ? $property : null;
    }
}
