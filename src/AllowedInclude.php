<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Includes\IncludedCount;
use Spatie\QueryBuilder\Includes\IncludedRelationship;
use Spatie\QueryBuilder\Includes\IncludeInterface;

class AllowedInclude
{
    /** @var string */
    protected $name;

    /** @var IncludeInterface */
    protected $includeClass;

    /** @var string|null */
    protected $internalName;

    public function __construct(string $name, IncludeInterface $includeClass, ?string $internalName = null)
    {
        $this->name = Str::camel($name);
        $this->includeClass = $includeClass;
        $this->internalName = $internalName ?? $this->name;
    }

    public static function relationship(string $name, ?string $internalName = null): Collection
    {
        $internalName = Str::camel($internalName ?? $name);

        return IncludedRelationship::getIndividualRelationshipPathsFromInclude($internalName)
            ->zip(IncludedRelationship::getIndividualRelationshipPathsFromInclude($name))
            ->flatMap(function ($args): Collection {
                [$relationship, $alias] = $args;

                $includes = collect([
                    new self($alias, new IncludedRelationship, $relationship),
                ]);

                if (! Str::contains($relationship, '.')) {
                    $suffix = config('query-builder.count_suffix');

                    $includes = $includes->merge(self::count(
                        $alias.$suffix,
                        $relationship.$suffix
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

    public function include(QueryBuilder $query): void
    {
        ($this->includeClass)($query, $this->internalName);
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
