<?php

namespace Spatie\QueryBuilder;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\ForwardsCalls;
use Spatie\QueryBuilder\Concerns\AddsFieldsToQuery;
use Spatie\QueryBuilder\Concerns\AddsIncludesToQuery;
use Spatie\QueryBuilder\Concerns\FiltersQuery;
use Spatie\QueryBuilder\Concerns\SortsQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSubject;

/**
 * @mixin EloquentBuilder
 */
class QueryBuilder implements ArrayAccess
{
    use FiltersQuery;
    use SortsQuery;
    use AddsIncludesToQuery;
    use AddsFieldsToQuery;
    use ForwardsCalls;

    /** @var \Spatie\QueryBuilder\QueryBuilderRequest */
    protected $request;

    /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation */
    protected $subject;

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $subject
     * @param null|\Illuminate\Http\Request $request
     */
    public function __construct($subject, ?Request $request = null)
    {
        $this->initializeSubject($subject)
            ->initializeRequest($request ?? app(Request::class));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $subject
     *
     * @return $this
     */
    protected function initializeSubject($subject): static
    {
        throw_unless(
            $subject instanceof EloquentBuilder || $subject instanceof Relation,
            InvalidSubject::make($subject)
        );

        $this->subject = $subject;

        return $this;
    }

    protected function initializeRequest(?Request $request = null): static
    {
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);

        return $this;
    }

    public function getEloquentBuilder(): EloquentBuilder
    {
        if ($this->subject instanceof EloquentBuilder) {
            return $this->subject;
        }

        if ($this->subject instanceof Relation) {
            return $this->subject->getQuery();
        }

        throw InvalidSubject::make($this->subject);
    }

    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param EloquentBuilder|Relation|string $subject
     * @param Request|null $request
     *
     * @return static
     */
    public static function for($subject, ?Request $request = null): static
    {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        return new static($subject, $request);
    }

    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        /*
         * If the forwarded method call is part of a chain we can return $this
         * instead of the actual $result to keep the chain going.
         */
        if ($result === $this->subject) {
            return $this;
        }

        return $result;
    }

    public function clone()
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->subject = clone $this->subject;
    }

    public function __get($name)
    {
        return $this->subject->{$name};
    }

    public function __set($name, $value)
    {
        $this->subject->{$name} = $value;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->subject[$offset]);
    }

    public function offsetGet($offset): bool
    {
        return $this->subject[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->subject[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->subject[$offset]);
    }
}
