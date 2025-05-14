<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FiltersBelongsTo implements Filter
{
    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        $values = $this->prepareValues($value);
        $valuesWithoutNulls = $this->filterNullValues($values);
        $withWhereNull = count($values) !== count($valuesWithoutNulls);

        if (empty($valuesWithoutNulls) && ! $withWhereNull) {
            return $query;
        }

        $propertyParts = collect(explode('.', $property));
        $relation = $propertyParts->pop();
        $relationParent = $propertyParts->implode('.');
        if ($relationParent) {
            $relationObject = $this->getRelationModelFromRelationName(
                $this->getModelFromRelationName($query->getModel(), $relationParent),
                $relation
            );
            $query->whereHas($relationParent, function (Builder $q) use ($relationObject, $withWhereNull, $valuesWithoutNulls) {
                $this->applyLastLevelWhere($q, $relationObject, $withWhereNull, $valuesWithoutNulls);
            });
        } else {
            $relationObject = $this->getRelationModelFromRelationName($query->getModel(), $relation);
            $this->applyLastLevelWhere($query, $relationObject, $withWhereNull, $valuesWithoutNulls);
        }
    }

    protected function prepareValues($values): array
    {
        return array_values(Arr::wrap($values));
    }

    protected function filterNullValues(array $values): array
    {
        return array_filter(
            $values,
            fn ($v) => ! in_array($v, [null, 0, 'null', '0', ''], true)
        );
    }

    protected function applyLastLevelWhere(Builder $query, BelongsTo $relation, bool $withWhereNull, array $values)
    {
        $relationColumn = $relation->getQualifiedForeignKeyName();
        $query->where(function (Builder $q) use ($relationColumn, $withWhereNull, $values) {
            if ($withWhereNull) {
                $q->orWhereNull($relationColumn);
            }
            if (! empty($values)) {
                $q->orWhereIn($relationColumn, $values);
            }
        });
    }

    protected function getModelFromRelationName(Model $model, string $relation, int $level = 0): Model
    {
        $relationParts = explode('.', $relation);
        if (count($relationParts) == 1) {
            $relationObject = $this->getRelationModelFromRelationName($model, $relation);

            return $relationObject->getRelated();
        }

        $firstRelation = $relationParts[0];
        $firstRelationObject = $this->getRelationModelFromRelationName($model, $firstRelation);

        // recursion
        return $this->getModelFromRelationName(
            $firstRelationObject->getRelated(),
            implode('.', array_slice($relationParts, 1)),
            $level + 1
        );
    }

    protected function getRelationModelFromRelationName(Model $model, string $relationName): BelongsTo
    {
        $relationObject = $model->$relationName();
        if (! $relationObject instanceof BelongsTo) {
            throw RelationNotFoundException::make($model, $relationName);
        }

        return $relationObject;
    }
}
