<?php

namespace Spatie\QueryBuilder;

use Closure;
use Spatie\QueryBuilder\Includes\IncludedAvg;
use Spatie\QueryBuilder\Includes\IncludedCallback;
use Spatie\QueryBuilder\Includes\IncludedCount;
use Spatie\QueryBuilder\Includes\IncludedExists;
use Spatie\QueryBuilder\Includes\IncludedMax;
use Spatie\QueryBuilder\Includes\IncludedMin;
use Spatie\QueryBuilder\Includes\IncludedRelationship;
use Spatie\QueryBuilder\Includes\IncludedSum;
use Spatie\QueryBuilder\Includes\IncludeInterface;

class AllowedInclude
{
    protected string $internalName;

    public function __construct(
        protected string $name,
        protected IncludeInterface $includeClass,
        ?string $internalName = null,
    ) {
        $this->internalName = $internalName ?? $this->name;
    }

    public static function relationship(string $name, ?string $internalName = null): static
    {
        return new static($name, new IncludedRelationship(), $internalName);
    }

    public static function count(string $name, ?string $internalName = null, ?Closure $constraint = null): static
    {
        return new static($name, new IncludedCount($constraint), $internalName);
    }

    public static function exists(string $name, ?string $internalName = null, ?Closure $constraint = null): static
    {
        return new static($name, new IncludedExists($constraint), $internalName);
    }

    public static function min(string $name, string $relation, string $column, ?string $internalName = null, ?Closure $constraint = null): static
    {
        return new static($name, new IncludedMin($relation, $column, $constraint), $internalName);
    }

    public static function max(string $name, string $relation, string $column, ?string $internalName = null, ?Closure $constraint = null): static
    {
        return new static($name, new IncludedMax($relation, $column, $constraint), $internalName);
    }

    public static function sum(string $name, string $relation, string $column, ?string $internalName = null, ?Closure $constraint = null): static
    {
        return new static($name, new IncludedSum($relation, $column, $constraint), $internalName);
    }

    public static function avg(string $name, string $relation, string $column, ?string $internalName = null, ?Closure $constraint = null): static
    {
        return new static($name, new IncludedAvg($relation, $column, $constraint), $internalName);
    }

    public static function callback(string $name, Closure $callback, ?string $internalName = null): static
    {
        return new static($name, new IncludedCallback($callback), $internalName);
    }

    public static function custom(string $name, IncludeInterface $includeClass, ?string $internalName = null): static
    {
        return new static($name, $includeClass, $internalName);
    }

    public function include(QueryBuilder $query): void
    {
        if ($this->includeClass instanceof IncludedRelationship) {
            $this->includeClass->setFieldsCallback(
                fn (string $relation, ?string $tableName = null) => $query->getRequestedFieldsForRelatedTable($relation, $tableName)
            );
        }

        ($this->includeClass)($query->getEloquentBuilder(), $this->internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForInclude(string $includeName): bool
    {
        return $this->name === $includeName;
    }
}
