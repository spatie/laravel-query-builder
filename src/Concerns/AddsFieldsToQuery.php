<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\ColumnNameSanitizer;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;

trait AddsFieldsToQuery
{
    /** @var \Illuminate\Support\Collection */
    private $allowedFields;

    public function allowedFields($fields): self
    {
        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                return $this->prependField($fieldName);
            });

        $this->guardAgainstUnknownFields();

        $this->addRequestedModelFieldsToQuery();

        return $this;
    }

    private function addRequestedModelFieldsToQuery()
    {
        $modelTableName = $this->getModel()->getTable();

        $modelFields = $this->request->fields()->get($modelTableName);

        if (empty($modelFields)) {
            return;
        }

        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        $this->select($prependedFields);
    }

    private function getFieldsForRelatedTable(string $relation): array
    {
        // This method is being called from the `allowedIncludes` section of the query builder.
        // If `allowedIncludes` is called before `allowedFields` we don't know what fields to
        // sanitize yet so we'll sanitize all of them. This is an edge case that will be fixed
        // in version 2 of the package.
        // TL;DR: Put `allowedFields` before `allowedIncludes`

        $fields = $this->request->fields()->get($relation, []);

        if ($this->allowedFields instanceof Collection) {
            // AllowedFields method has already sanitized for us.

            return $fields;
        }

        // Empty allowed fields means they're all allowed by default.
        // Sanitize all of them to be safe.

        return ColumnNameSanitizer::sanitizeArray($fields);
    }

    private function guardAgainstUnknownFields()
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

    private function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return $this->prependField($field, $tableName);
        }, $fields);
    }

    private function prependField(string $field, ?string $table = null): string
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
