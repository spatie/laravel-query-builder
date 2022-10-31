<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FiltersPartial extends FiltersExact implements Filter
{
    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        if (is_array($value)) {
            if (count(array_filter($value, 'strlen')) === 0) {
                return $query;
            }

            $query->where(function (Builder $query) use ($value, $sql, $property) {
                foreach (array_filter($value, 'strlen') as $partialValue) {
                    $this->applyWhere($query, $partialValue, $property);
                }
            });

            return;
        }

        $this->applyWhere($query, $value, $property);
    }

    protected function applyWhere(Builder $query, $value, string $property)
    {
        $value = mb_strtolower($value, 'UTF8');

        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));

        $query->whereRaw("LOWER({$wrappedProperty}) LIKE ?", ["%{$value}%"]);
    }
}
