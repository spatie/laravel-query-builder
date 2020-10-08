<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\FiltersTrashed;
use Spatie\QueryBuilder\Filters\FiltersCallback;

class AllowedFilter
{
    /** @var mixed */
    protected $default;

    /** @var \Spatie\QueryBuilder\Filters\Filter */
    protected $filterClass;

    /** @var \Illuminate\Support\Collection */
    protected $ignored;

    /** @var string */
    protected $internalName;

    /** @var string */
    protected $name;

    function default($value): self{
        $this->default = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param Filter $filterClass
     * @param string $internalName
     */
    public function __construct(string $name, Filter $filterClass,  ? string $internalName = null)
    {
        $this->name = $name;

        $this->filterClass = $filterClass;

        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    /**
     * @param string $name
     * @param $callback
     * @param $internalName
     * @param nullstring $arrayValueDelimiter
     */
    public static function callback(string $name, $callback, $internalName = null, string $arrayValueDelimiter = null) : self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersCallback($callback), $internalName);
    }

    /**
     * @param string $name
     * @param Filter $filterClass
     * @param $internalName
     * @param nullstring $arrayValueDelimiter
     */
    public static function custom(string $name, Filter $filterClass, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, $filterClass, $internalName);
    }

    /**
     * @param string $name
     * @param string $internalName
     * @param nullbool $addRelationConstraint
     * @param truestring $arrayValueDelimiter
     */
    public static function exact(string $name,  ? string $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null) : self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersExact($addRelationConstraint), $internalName);
    }

    /**
     * @param QueryBuilder $query
     * @param $value
     */
    public function filter(QueryBuilder $query, $value)
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        ($this->filterClass)($query->getEloquentBuilder(), $valueToFilter, $this->internalName);
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return mixed
     */
    public function getIgnored(): array
    {
        return $this->ignored->toArray();
    }

    /**
     * @return mixed
     */
    public function getInternalName(): string
    {
        return $this->internalName;
    }

    /**
     * @return mixed
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function hasDefault(): bool
    {
        return isset($this->default);
    }

    /**
     * @param $values
     * @return mixed
     */
    public function ignore(...$values): self
    {
        $this->ignored = $this->ignored
            ->merge($values)
            ->flatten();

        return $this;
    }

    /**
     * @param string $filterName
     * @return mixed
     */
    public function isForFilter(string $filterName): bool
    {
        return $this->name === $filterName;
    }

    /**
     * @param string $name
     * @param $internalName
     * @param nullbool $addRelationConstraint
     * @param truestring $arrayValueDelimiter
     */
    public static function partial(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersPartial($addRelationConstraint), $internalName);
    }

    /**
     * @param string $name
     * @param $internalName
     * @param nullstring $arrayValueDelimiter
     */
    public static function scope(string $name, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FiltersScope(), $internalName);
    }

    /**
     * @param string $delimiter
     */
    public static function setFilterArrayValueDelimiter(string $delimiter = null): void
    {
        if (isset($delimiter)) {
            QueryBuilderRequest::setFilterArrayValueDelimiter($delimiter);
        }
    }

    /**
     * @param string $name
     * @param $internalName
     */
    public static function trashed(string $name = 'trashed', $internalName = null): self
    {
        return new static($name, new FiltersTrashed(), $internalName);
    }

    /**
     * @param $value
     */
    protected function resolveValueForFiltering($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_diff_assoc($value, $this->ignored->toArray());

            return !empty($remainingProperties) ? $remainingProperties : null;
        }

        return !$this->ignored->contains($value) ? $value : null;
    }
}
