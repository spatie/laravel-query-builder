<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\Includes\IncludedRelationship;

class Included
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $includeClass;

    /** @var string|null */
    protected $relationship;

    public function __construct(string $name, string $includeClass, ?string $relationship = null)
    {
        $this->name = Str::camel($name);
        $this->includeClass = $includeClass;
        $this->relationship = $relationship ?? $this->name;
    }

    public static function relationship(string $name, ?string $relationship = null): self
    {
        return new self($name, IncludedRelationship::class, $relationship);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
