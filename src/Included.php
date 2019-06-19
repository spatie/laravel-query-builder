<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Includes\Includable;
use Spatie\QueryBuilder\Includes\IncludedCount;
use Spatie\QueryBuilder\Includes\IncludedRelationship;

class Included
{
    /** @var string */
    protected $name;

    /** @var Includable */
    protected $includeClass;

    /** @var string|null */
    protected $relationship;

    public function __construct(string $name, Includable $includeClass, ?string $relationship = null)
    {
        $this->name = Str::camel($name);
        $this->includeClass = $includeClass;
        $this->relationship = $relationship ?? $this->name;
    }

    public static function relationship(string $relationship, ?string $include = null): Collection
    {
        return static::getIndividualRelationshipPathsFromInclude($relationship)
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

    protected static function getIndividualRelationshipPathsFromInclude(string $include): Collection
    {
        return collect(explode('.', $include))
            ->reduce(function ($includes, $relationship) {
                if ($includes->isEmpty()) {
                    return $includes->push($relationship);
                }

                return $includes->push("{$includes->last()}.{$relationship}");
            }, collect());
    }
}
