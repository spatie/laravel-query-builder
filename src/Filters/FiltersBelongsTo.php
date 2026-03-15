<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements Filter<TModelClass>
 */
class FiltersBelongsTo implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
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
            return;
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

        return $this->getRelatedModelFromRelation($modelParent, $relationName);
    }

    protected function getRelatedModelFromRelation(Model $model, string $relationName): Model
    {
        $relationObject = $model->$relationName();
        if (! $relationObject instanceof Relation) {
            throw RelationNotFoundException::make($model, $relationName);
        }

        return $relationObject->getRelated();
    }

    protected function getModelFromRelation(Model $model, string $relation, int $level = 0): ?Model
    {
        $relationParts = explode('.', $relation);
        if (count($relationParts) == 1) {
            return $this->getRelatedModelFromRelation($model, $relation);
        }

        $firstRelation = $relationParts[0];
        $firstRelatedModel = $this->getRelatedModelFromRelation($model, $firstRelation);

        return $this->getModelFromRelation($firstRelatedModel, implode('.', array_slice($relationParts, 1)), $level + 1);
    }
}
