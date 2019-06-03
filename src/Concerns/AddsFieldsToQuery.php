<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\ColumnNameSanitizer;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;

trait AddsFieldsToQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedFields;

    public function allowedFields($fields): self
    {
        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                if (! Str::contains($fieldName, '.')) {
                    $modelTableName = $this->getModel()->getTable();

                    return "{$modelTableName}.{$fieldName}";
                }

                return $fieldName;
            });

        $this->guardAgainstUnknownFields();

        $this->addModelFieldsToQuery();

        return $this;
    }

    public function addAllRequestedFields()
    {
        if ($this->allowedFields instanceof Collection) {
            // If we have allowed fields we will have parsed them in the allowed fields method.

            return;
        }

        $this
            ->getRequestedFields()
            ->each(function (array $fields, string $table) {
                return ColumnNameSanitizer::sanitizeArray($fields);
            });

        $this->addModelFieldsToQuery();
    }

    protected function getFieldsForRelatedTable(string $relation): array
    {
        // This method is being called from the `allowedIncludes` section of the query builder.
        // If `allowedIncludes` is called before `allowedFields` we don't know what fields to
        // sanitize yet so we'll sanitize all of them. This is an edge case that will be fixed
        // in version 2 of the package.
        // TL;DR: Put `allowedFields` before `allowedIncludes`

        $fields = $this->getRequestedFields()->get($relation, []);

        if ($this->allowedFields instanceof Collection) {
            // AllowedFields method has already sanitized for us.

            return $fields;
        }

        // Empty allowed fields means they're all allowed by default.
        // Sanitize all of them to be safe.

        return ColumnNameSanitizer::sanitizeArray($fields);
    }

    protected function getRequestedFields(): Collection
    {
        return $this->request->fields();
    }

    protected function guardAgainstUnknownFields()
    {
        $fields = $this->getRequestedFields()
            ->map(function ($fields, $model) {
                $tableName = Str::snake(preg_replace('/-/', '_', $model));

                $fields = array_map([Str::class, 'snake'], $fields);

                return $this->prependFieldsWithTableName($fields, $tableName);
            })
            ->flatten()
            ->unique();

        $diff = $fields->diff($this->allowedFields);

        if ($diff->count()) {
            throw InvalidFieldQuery::fieldsNotAllowed($diff, $this->allowedFields);
        }
    }

    protected function addModelFieldsToQuery()
    {
        $modelTableName = $this->getModel()->getTable();

        $modelFields = $this->getRequestedFields()->get($modelTableName);

        if (empty($modelFields)) {
            return;
        }

        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        foreach ($prependedFields as $field) {
            $this->addSelect($field);
        }
    }

    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return "{$tableName}.{$field}";
        }, $fields);
    }
}
