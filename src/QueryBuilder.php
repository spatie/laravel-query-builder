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

/**
 * @template TModel of Model
 * @mixin EloquentBuilder<TModel>
 */
class QueryBuilder implements ArrayAccess
{
    use AddsFieldsToQuery;
    use AddsIncludesToQuery;
    use FiltersQuery;
    use ForwardsCalls;
    use SortsQuery;

    protected QueryBuilderRequest $request;

    /**
     * @param EloquentBuilder<TModel>|Relation<TModel, *, *> $subject
     */
    public function __construct(
        protected EloquentBuilder|Relation $subject,
        ?Request $request = null,
    ) {
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);
    }

    /**
     * @return EloquentBuilder<TModel>
     */
    public function getEloquentBuilder(): EloquentBuilder
    {
        if ($this->subject instanceof EloquentBuilder) {
            return $this->subject;
        }

        return $this->subject->getQuery();
    }

    /**
     * @return Relation<TModel, *, *>|EloquentBuilder<TModel>
     */
    public function getSubject(): Relation|EloquentBuilder
    {
        return $this->subject;
    }

    /**
     * @template T of Model
     *
     * @param EloquentBuilder<T>|Relation<T, *, *>|class-string<T> $subject
     * @return static<T>
     */
    public static function for(
        EloquentBuilder|Relation|string $subject,
        ?Request $request = null,
    ): static {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        /** @var static<T> $queryBuilder */
        $queryBuilder = new static($subject, $request);

        return $queryBuilder;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);

        if ($result === $this->subject) {
            return $this;
        }

        return $result;
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->subject = clone $this->subject;
    }

    public function __get(string $name): mixed
    {
        return $this->subject->{$name};
    }

    public function __set(string $name, mixed $value): void
    {
        $this->subject->{$name} = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->subject[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->subject[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->subject[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->subject[$offset]);
    }
}
