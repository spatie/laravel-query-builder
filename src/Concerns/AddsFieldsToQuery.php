<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\UnknownIncludedFieldsQuery;
use Spatie\QueryBuilder\Exceptions\AllowedIncludesBeforeAllowedFields;

trait AddsFieldsToQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFields;

    public function allowedFields($fields): self
    {
        if ($this->allowedIncludes instanceof Collection) {
            throw new AllowedIncludesBeforeAllowedFields();
        }

        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                return $this->prependField($fieldName);
            });

        $this->guardAgainstUnknownFields();

        $this->addRequestedModelFieldsToQuery();

        return $this;
    }

    protected function addRequestedModelFieldsToQuery()
    {
        $modelTableName = $this->getModel()->getTable();

        $modelFields = $this->request->fields()->get($modelTableName);

        if (empty($modelFields)) {
            return;
        }

        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        $this->select($prependedFields);
    }

    protected function getRequestedFieldsForRelatedTable(string $relation): array
    {
        // This method is being called from the `allowedIncludes` section of the query builder.
        // If `allowedIncludes` is called before `allowedFields` we don't know what fields to
        // allow yet so we'll throw an exception.
        // TL;DR: Put `allowedFields` before `allowedIncludes`

        $fields = $this->request->fields()->get($relation);

        if (! $fields) {
            return [];
        }

        if (! $this->allowedFields instanceof Collection) {
            // We have requested fields but no allowed fields (yet?)

            throw new UnknownIncludedFieldsQuery($fields);
        }

        return $fields;
    }

    protected function guardAgainstUnknownFields()
    {
        $requestedFields = $this->request->fields()
            ->map(function ($fields, $model) {
                $tableName = Str::snake(preg_replace('/-/', '_', $model));

                $fields = array_map([Str::class, 'snake'], $fields);

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

    protected function prependField(string $field, ?string $table = null): string
    {
        if (! $table) {
            $table = $this->getModel()->getTable();
        }

        if (Str::contains($field, '.')) {
            // Already prepended

            return $field;
        }

        return "{$table}.{$field}";
    }
}
