<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Includes\IncludeInterface;
use Spatie\QueryBuilder\Includes\IncludedCount;
use Spatie\QueryBuilder\Includes\IncludedRelationship;

class AllowedInclude
{
    /** @var string */
    protected $name;

    /** @var IncludeInterface */
    protected $includeClass;

    /** @var string|null */
    protected $relationship;

    public function __construct(string $name, IncludeInterface $includeClass, ?string $relationship = null)
    {
        $this->name = Str::camel($name);
        $this->includeClass = $includeClass;
        $this->relationship = $relationship ?? $this->name;
    }

    public static function relationship(string $relationship, ?string $include = null): Collection
    {
        return IncludedRelationship::getIndividualRelationshipPathsFromInclude($relationship)
            ->flatMap(function (string $relationship) use ($include): Collection {
                $includes = collect([
                    new self($relationship, new IncludedRelationship(), $include),
                ]);

                if (! Str::contains($relationship, '.')) {
                    $includes = $includes->merge(self::count($relationship.'Count', $include ? $include.'Count' : null));
                }

                return $includes;
            });
    }

    public static function count(string $relationship, ?string $include = null): Collection
    {
        return collect([
            new self($relationship, new IncludedCount(), $include),
        ]);
    }

    public function include(Builder $builder)
    {
        ($this->includeClass)($builder, $this->relationship);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForInclude(string $include): bool
    {
        return $this->name === $include;
    }
}
