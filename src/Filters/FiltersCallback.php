<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * FiltersCallback provides filtering based on a PHP callback.
 *
 * Such callback should follow signature of {@see \Spatie\QueryBuilder\Filters\Filter::__invoke()}:
 *
 * ```php
 * function (\Illuminate\Database\Eloquent\Builder $builder, mixed $value, string $property)
 * ```
 *
 * For example:
 *
 * ```php
 * QueryBuilder::for(Item::class)
 *     ->allowedFilters([
 *         AllowedFilter::callback('trashed', function (Builder $query, $value) {
 *             if ($value === 'only') {
 *                 return $query->onlyTrashed();
 *             }
 *
 *             if ($value === 'with') {
 *                 return $query->withTrashed();
 *             }
 *
 *             $query->withoutTrashed();
 *         }),
 *     ]);
 * ```
 *
 * @see \Spatie\QueryBuilder\AllowedFilter::callback()
 */
class FiltersCallback implements Filter
{
    /**
     * @var callable a PHP callback of the following signature:
     * `function (\Illuminate\Database\Eloquent\Builder $builder, mixed $value, string $property)`
     */
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        return call_user_func($this->callback, $query, $value, $property);
    }
}
