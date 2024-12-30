<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $values = array_values(Arr::wrap($value));

        $propertyParts = collect(explode('.', $property));
        $relation = $propertyParts->pop();
        $relationParent = $propertyParts->implode('.');
        $relatedModel = $this->getRelatedModel($query->getModel(), $relation, $relationParent);

        $relatedCollection = $relatedModel->newCollection();
        array_walk($values, fn ($v) => $relatedCollection->add(
            tap($relatedModel->newInstance(), fn ($m) => $m->setAttribute($m->getKeyName(), $v))
        ));

        if ($relatedCollection->isEmpty()) {
            return $query;
        }

        if ($relationParent) {
            $query->whereHas($relationParent, fn (Builder $q) => $q->whereBelongsTo($relatedCollection, $relation));
        } else {
            $query->whereBelongsTo($relatedCollection, $relation);
        }
    }

    protected function getRelatedModel(Model $modelQuery, string $relationName, string $relationParent): Model
    {
        if ($relationParent) {
            $modelParent = $this->getModelFromRelation($modelQuery, $relationParent);
        } else {
            $modelParent = $modelQuery;
        }

        $relatedModel = $this->getRelatedModelFromRelation($modelParent, $relationName);

        return $relatedModel;
    }

    protected function getRelatedModelFromRelation(Model $model, string  $relationName): ?Model
    {
        $relationObject = $model->$relationName();
        if (! is_subclass_of($relationObject, Relation::class)) {
            throw RelationNotFoundException::make($model, $relationName);
        }

        $relatedModel = $relationObject->getRelated();

        return $relatedModel;
    }

    protected function getModelFromRelation(Model $model, string $relation, int $level = 0): ?Model
    {
        $relationParts = explode('.', $relation);
        if (count($relationParts) == 1) {
            return $this->getRelatedModelFromRelation($model, $relation);
        }

        $firstRelation = $relationParts[0];
        $firstRelatedModel = $this->getRelatedModelFromRelation($model, $firstRelation);
        if (! $firstRelatedModel) {
            return null;
        }

        return $this->getModelFromRelation($firstRelatedModel, implode('.', array_slice($relationParts, 1)), $level + 1);
    }
}
