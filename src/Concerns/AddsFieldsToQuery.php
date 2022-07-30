<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\QueryBuilder\Exceptions\AllowedFieldsMustBeCalledBeforeAllowedIncludes;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\UnknownIncludedFieldsQuery;

trait AddsFieldsToQuery
{
    protected ?Collection $allowedFields = null;

    public function allowedFields($fields): self
    {
        if ($this->allowedIncludes instanceof Collection) {
            throw new AllowedFieldsMustBeCalledBeforeAllowedIncludes();
        }

        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                return $this->prependField($fieldName);
            });

        $this->ensureAllFieldsExist();

        $this->addRequestedModelFieldsToQuery();

        return $this;
    }

    protected function addRequestedModelFieldsToQuery()
    {
        $modelName = lcfirst((new ReflectionClass($this->getModel()))->getShortName());

        $modelFields = $this->request->fields()->get($modelName);

        if (empty($modelFields)) {
            return;
        }

        $modelTableName = $this->getModel()->getTable();
        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        $this->select($prependedFields);
    }

    public function getRequestedFieldsForRelatedTable(string $relation): array
    {
        $fields = $this->request->fields()->mapWithKeys(function ($fields, $table) {
            return [$table => $fields];
        })->get($relation);

        if (! $fields) {
            return [];
        }

        if (! $this->allowedFields instanceof Collection) {
            // We have requested fields but no allowed fields (yet?)

            throw new UnknownIncludedFieldsQuery($fields);
        }

        return array_unique([
            ...$this->resolveAdditionallyRequiredKeys($relation),
            ...$fields,
        ]);
    }

    protected function ensureAllFieldsExist()
    {
        $requestedFields = $this->request->fields()
            ->map(function ($fields, $model) {
                $tableName = $model;

                return $this->prependFieldsWithTableName($fields, $tableName);
            })
            ->flatten()
            ->unique();

        $unknownFields = $requestedFields->diff($this->allowedFields);

        if ($unknownFields->isNotEmpty()) {
            throw InvalidFieldQuery::fieldsNotAllowed($unknownFields, $this->allowedFields);
        }
    }

    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return $this->prependField($field, $tableName);
        }, $fields);
    }

    protected function prependField(string $field, ?string $modelName = null): string
    {
        if (! $modelName) {
            $modelName = lcfirst((new ReflectionClass($this->getModel()))->getShortName());
        }

        if (Str::contains($field, '.')) {
            // Already prepended

            return $field;
        }

        return "{$modelName}.{$field}";
    }

    /**
     * We need the primary key of any associated relation model.
     * We also need the foreign key to associate the included model correctly
     *
     * Navigate the graph for nested relations to find (possibly) nested keys
     * and foreign keys.
     *
     * @param string $relation
     *
     * @return string[]
     */
    protected function resolveAdditionallyRequiredKeys(string $relation): array
    {
        [$nestedModel, $nestedRelation] =
            collect(explode('.', $relation))
            ->reduce(fn ($acc, $relationPart) => [
                $acc[0]->$relationPart()->getModel(),
                $acc[0]->$relationPart(),
            ], [$this->getModel(), null]);

        $requiredKeys = [ $nestedModel->getKeyName() ];

        // We need to query the foreign key only if it is saved on the table
        // of the related model
        if (! ($nestedRelation instanceof BelongsTo)) {
            $requiredKeys[] = $nestedRelation->getForeignKeyName();
        }

        return $requiredKeys;
    }
}
