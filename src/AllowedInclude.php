<?php

namespace Spatie\QueryBuilder;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Includes\IncludedCallback;
use Spatie\QueryBuilder\Includes\IncludedCount;
use Spatie\QueryBuilder\Includes\IncludedExists;
use Spatie\QueryBuilder\Includes\IncludedRelationship;
use Spatie\QueryBuilder\Includes\IncludeInterface;

class AllowedInclude
{
    protected string $internalName;

    public function __construct(
        protected string $name,
        protected IncludeInterface $includeClass,
        ?string $internalName = null
    ) {
        $this->internalName = $internalName ?? $this->name;
    }

    public static function relationship(string $name, ?string $internalName = null): Collection
    {
        $internalName = $internalName ?? $name;

        return IncludedRelationship::getIndividualRelationshipPathsFromInclude($internalName)
            ->zip(IncludedRelationship::getIndividualRelationshipPathsFromInclude($name))
            ->flatMap(function ($args): Collection {
                [$relationship, $alias] = $args;

                $includes = collect([
                    new self($alias, new IncludedRelationship(), $relationship),
                ]);

                if (! Str::contains($relationship, '.')) {
                    $countSuffix = config('query-builder.count_suffix', 'Count');
                    $existsSuffix = config('query-builder.exists_suffix', 'Exists');

                    $includes = $includes
                        ->merge(self::count(
                            $alias.$countSuffix,
                            $relationship.$countSuffix
                        ))
                        ->merge(self::exists(
                            $alias.$existsSuffix,
                            $relationship.$existsSuffix
                        ));
                }

                return $includes;
            });
    }

    public static function count(string $name, ?string $internalName = null): Collection
    {
        return collect([
            new static($name, new IncludedCount(), $internalName),
        ]);
    }

    public static function exists(string $name, ?string $internalName = null): Collection
    {
        return collect([
            new static($name, new IncludedExists(), $internalName),
        ]);
    }

    public static function callback(string $name, Closure $callback, ?string $internalName = null): Collection
    {
        return collect([
            new static($name, new IncludedCallback($callback), $internalName),
        ]);
    }

    public static function custom(string $name, IncludeInterface $includeClass, ?string $internalName = null): Collection
    {
        return collect([
            new static($name, $includeClass, $internalName),
        ]);
    }

    public function include(QueryBuilder $query): void
    {
        if (property_exists($this->includeClass, 'getRequestedFieldsForRelatedTable')) {
            $this->includeClass->getRequestedFieldsForRelatedTable = function (...$args) use ($query) {
                return $query->getRequestedFieldsForRelatedTable(...$args);
            };
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
