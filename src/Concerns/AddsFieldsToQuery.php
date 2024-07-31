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
    public ?Collection $allowedFields = null;

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

    protected function addRequestedModelFieldsToQuery(): void
    {
        $modelTableName = $this->getModel()->getTable();

        $requestFields = $this->request->fields()->map(function ($field) {
            return $field->name;
        });

        $modelFields = $this->allowedFields->mapWithKeys(function (AllowedField $field) {
            return [
                $field->getName() => $field->getInternalNames()->toArray(),
            ];
        });

        if ($requestFields->count() > 0) {
            // If fields are requested, only select those
            $modelFields = $modelFields->filter(function ($internalName, $name) use ($requestFields) {
                return $requestFields->contains($name);
            })->toArray();
        } else {
            // If no fields are requested, select all allowed fields
            $modelFields = $modelFields->toArray();
        }

        if (empty($modelFields)) {
            return;
        }

        // Flatten array
        $modelFields = array_unique(array_merge(...array_values($modelFields)));

        // Prepend the fields with the table name
        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        $this->select($prependedFields);
    }

    public function getRequestedFieldsForRelatedTable(string $relation): array
    {
        $tableOrRelation = config('query-builder.convert_relation_names_to_snake_case_plural', true)
            ? Str::plural(Str::snake($relation))
            : $relation;

        $fields = $this->request->fields()
            ->mapWithKeys(fn ($fields, $table) => [$table => $fields])
            ->get($tableOrRelation);

        if (! $fields) {
            return [];
        }

        if (! $this->allowedFields instanceof Collection) {
            // We have requested fields but no allowed fields (yet?)
            throw new UnknownIncludedFieldsQuery($fields);
        }

        return $fields;
    }

    protected function ensureAllFieldsExist(): void
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

    public function getAllowedFields(): ?Collection
    {
        return $this->allowedFields;
    }
}
