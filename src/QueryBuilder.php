<?php

namespace Spatie\QueryBuilder;

use ArrayAccess;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use LogicException;
use Spatie\QueryBuilder\Concerns\AddsFieldsToQuery;
use Spatie\QueryBuilder\Concerns\AddsIncludesToQuery;
use Spatie\QueryBuilder\Concerns\AppendsAttributesToResults;
use Spatie\QueryBuilder\Concerns\FiltersQuery;
use Spatie\QueryBuilder\Concerns\SortsQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSubject;

/**
 * @mixin EloquentBuilder
 */
class QueryBuilder implements ArrayAccess
{
    use FiltersQuery,
        SortsQuery,
        AddsIncludesToQuery,
        AddsFieldsToQuery,
        AppendsAttributesToResults,
        ForwardsCalls;

    /** @var QueryBuilderRequest */
    protected $request;

    /** @var EloquentBuilder|Relation */
    protected $subject;

    public function __construct($subject, ?Request $request = null)
    {
        $this->initializeSubject($subject)
            ->initializeRequest($request ?? app(Request::class));
    }

    protected function initializeSubject($subject): self
    {
        throw_unless(
            $subject instanceof EloquentBuilder || $subject instanceof Relation,
            InvalidSubject::make($subject)
        );

        $this->subject = $subject;

        return $this;
    }

    protected function initializeRequest(?Request $request = null): self
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
     * @return static
     */
    public static function for($subject, ?Request $request = null): self
    {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        return new static($subject, $request);
    }

    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        if ($result === $this->subject) {
            return $this;
        }

        if ($result instanceof Model) {
            $this->addAppendsToResults(collect([$result]));
        }

        if ($result instanceof Collection) {
            $this->addAppendsToResults($result);
        }

        return $result;
    }

    public function __get($name)
    {
        return $this->subject->{$name};
    }

    public function __set($name, $value)
    {
        $this->subject->{$name} = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->subject[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->subject[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->subject[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->subject[$offset]);
    }
}
