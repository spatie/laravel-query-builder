<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
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
                if (! str_contains($fieldName, '.')) {
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
        $this->addFieldsToQuery($this->request->fields());
    }

    protected function addFieldsToQuery(Collection $fields)
    {
        $modelTableName = $this->getModel()->getTable();

        if ($modelFields = $fields->get($modelTableName)) {
            $this->select($this->prependFieldsWithTableName($modelFields, $modelTableName));
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
        if ($this->request->fields()->isEmpty()) {
            return ['*'];
        }

        return $this->request->fields()->get($relation, []);
    }

    protected function guardAgainstUnknownFields()
    {
        $fields = $this->request->fields()
            ->map(function ($fields, $model) {
                $tableName = snake_case(preg_replace('/-/', '_', $model));

                $fields = array_map('snake_case', $fields);

                return $this->prependFieldsWithTableName($fields, $tableName);
            })
            ->flatten()
            ->unique();

        $diff = $fields->diff($this->allowedFields);

        if ($diff->count()) {
            throw InvalidFieldQuery::fieldsNotAllowed($diff, $this->allowedFields);
        }
    }
}
