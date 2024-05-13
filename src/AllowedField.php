<?php

namespace Spatie\QueryBuilder;

use Spatie\QueryBuilder\Filters\Filter;

class AllowedField
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $internalName;
    public function __construct(string $name, ?string $internalName = null)
    {
        $this->name = $name;

        $this->internalName = $internalName ?? $name;
    }


    public static function setFilterArrayValueDelimiter(string $delimiter = null): void
    {
        if (isset($delimiter)) {
            QueryBuilderRequest::setFilterArrayValueDelimiter($delimiter);
        }
    }

    public static function partial(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);
        return new static($name, $internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function getInternalName(): string
    {
        return $this->internalName;
    }
}
