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

        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));

        if (is_array($value)) {
            if (count(array_filter($value, 'strlen')) === 0) {
                return $query;
            }

            $query->where(function (Builder $query) use ($value, $wrappedProperty) {
                foreach (array_filter($value, 'strlen') as $partialValue) {
                    [$sql, $bindings] = $this->getWhereRawParameters($partialValue, $wrappedProperty);
                    $query->orWhereRaw($sql, $bindings);
                }
            });

            return;
        }

        [$sql, $bindings] = $this->getWhereRawParameters($value, $wrappedProperty);
        $query->whereRaw($sql, $bindings);
    }

    protected function getWhereRawParameters($value, string $property): array
    {
        $value = mb_strtolower((string) $value, 'UTF8');

        return [
            "LOWER({$property}) LIKE ?",
            ['%'.self::escapeLike($value).'%'],
        ];
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '_', '%'],
            ['\\\\', '\\_', '\\%'],
            $value,
        );
    }
}
