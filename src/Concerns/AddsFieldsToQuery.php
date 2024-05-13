<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedField;
use Spatie\QueryBuilder\Exceptions\AllowedFieldsMustBeCalledBeforeAllowedIncludes;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\UnknownIncludedFieldsQuery;

trait AddsFieldsToQuery
{
    protected ?Collection $allowedFields = null;

    public function allowedFields($fields): static
    {
        if ($this->allowedIncludes instanceof Collection) {
            throw new AllowedFieldsMustBeCalledBeforeAllowedIncludes();
        }

        $fields = is_array($fields) ? $fields : func_get_args();

        $this->allowedFields = collect($fields)->map(function ($field) {
            if ($field instanceof AllowedField) {
                return $field;
            }

            return AllowedField::partial($field);
        });

        $this->ensureAllFieldsExist();

        $this->addRequestedModelFieldsToQuery();

        return $this;
    }

    protected function addRequestedModelFieldsToQuery()
    {
        $modelTableName = $this->getModel()->getTable();

        $this->allowedFields->map(function (AllowedField $field) {
            if ($this->request->fields()->where('name', $field->getName())->count() > 0)
                return $field->getInternalName();
        })->toArray();

        if (empty($modelFields)) {
            return;
        }

        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        $this->select($prependedFields);
    }

    public function getRequestedFieldsForRelatedTable(string $relation): array
    {
        $table = Str::plural(Str::snake($relation)); // TODO: make this configurable

        $fields = $this->request->fields()->mapWithKeys(function ($fields, $table) {
            return [$table => $fields];
        })->get($table);

        if (! $fields) {
            return [];
        }

        if (! $this->allowedFields instanceof Collection) {
            // We have requested fields but no allowed fields (yet?)

            throw new UnknownIncludedFieldsQuery($fields);
        }

        return $fields;
    }

    protected function ensureAllFieldsExist()
    {
        // Map fieldnames from object
        $allowedFields = $this->allowedFields->map(function (AllowedField $field) {
            return $field->getName();
        });

        $requestedFields = $this->request->fields();

        $unknownFields = $requestedFields->pluck('name')->diff($allowedFields);

        if ($unknownFields->isNotEmpty()) {
            throw InvalidFieldQuery::fieldsNotAllowed($unknownFields, $allowedFields);
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
