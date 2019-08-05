<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Includes\IncludedCount;
use Spatie\QueryBuilder\Includes\IncludeInterface;
use Spatie\QueryBuilder\Includes\IncludedRelationship;

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
            ->flatMap(function (string $relationship) use ($name, $internalName): Collection {
                return collect([
                    new self($relationship, new IncludedRelationship(), $relationship === $internalName ? $internalName : null),
                ])
                    ->when(! Str::contains($relationship, '.'), function (Collection $includes) use ($internalName, $relationship) {
                        return $includes->merge(self::count("{$relationship}Count", $relationship === $internalName ? "{$internalName}Count" : null));
                    });
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
