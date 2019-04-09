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

        if (! $this->allowedFields->contains('*')) {
            $this->guardAgainstUnknownFields();
        }

        return $this;
    }

    public function parseFields()
    {
        $this->addFieldsToQuery($this->getRequestedFields());
    }

    protected function addFieldsToQuery(Collection $fields)
    {
        $modelTableName = $this->getModel()->getTable();

        if ($modelFields = $fields->get($modelTableName)) {
            $sanitizedFields = ColumnNameSanitizer::sanitizeArray($modelFields);

            $prependedFields = $this->prependFieldsWithTableName($sanitizedFields, $modelTableName);

            $this->select($prependedFields);
        }
    }

    protected function prependFieldsWithTableName(array $fields, string $tableName): array
    {
        return array_map(function ($field) use ($tableName) {
            return "{$tableName}.{$field}";
        }, $fields);
    }

    protected function getFieldsForIncludedTable(string $relation): array
    {
        return $this->getRequestedFields()->get($relation, []);
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

    protected function getRequestedFields(): Collection
    {
        return $this->request->fields();
    }
}
