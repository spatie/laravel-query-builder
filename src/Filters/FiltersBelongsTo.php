<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Exceptions\InvalidFilterProperty;

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
            if (!$modelParent) {
                throw InvalidFilterProperty::make($relationParent.'.'.$relationName);
            }
        } else {
            $modelParent = $modelQuery;
        }

        $relatedModel = $this->getRelatedModelFromRelation($modelParent, $relationName);
        if (!$relatedModel) {
            throw InvalidFilterProperty::make($relationParent.'.'.$relationName);
        }

        return $relatedModel;
    }

    protected function getRelatedModelFromRelation(Model $model, string  $relationName): ?Model
    {
        if (!method_exists($model, $relationName)) {
            return null;
        }

        $relationObject = $model->$relationName();
        if (!is_subclass_of ($relationObject, Relation::class)) {
            return null;
        }

        $relatedModel = $relationObject->getRelated();
        if (!is_subclass_of($relatedModel, Model::class)) {
            return null;
        }

        return $relatedModel;
    }

    protected function getModelFromRelation(Model $model, string $relation, int $level = 0): ?Model
    {
        $relationParts = explode('.', $relation);
        if (count($relationParts) == 1) {
            return $this->getRelatedModelFromRelation($model, $relation);
        } else {
            $firstRelation = $relationParts[0];
            $firstRelatedModel = $this->getRelatedModelFromRelation($model, $firstRelation);
            if (!$firstRelatedModel) {
                return null;
            }
            return $this->getModelFromRelation($firstRelatedModel, implode('.', array_slice($relationParts, 1)), $level + 1);
        }
    }
}
