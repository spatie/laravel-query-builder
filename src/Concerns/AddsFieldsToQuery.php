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

    public function allowedFields(string ...$fields): static
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

        if (config('query-builder.convert_relation_table_name_strategy', false) === 'camelCase') {
            $modelTableName = Str::camel($modelTableName);
        }

        if (config('query-builder.convert_relation_table_name_strategy', false) === 'snake_case') {
            $modelTableName = Str::snake($modelTableName);
        }

        $requestFields = $this->request->fields()->map(function ($field) {
            return $field->name;
        });

        $modelFields = $this->allowedFields->mapWithKeys(function (AllowedField $field) {
            return [
                $field->getName() => $field->getInternalNames(config('query-builder.convert_field_names_to_snake_case', false))->toArray(),
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

    public function getRequestedFieldsForRelatedTable(string $relation, ?string $tableName = null): array
    {
        // Build list of possible table names to check
        $possibleRelatedNames = [];

        // Original table name conversion logic
        $possibleRelatedNames[] = config('query-builder.convert_relation_names_to_snake_case_plural', true)
            ? Str::plural(Str::snake($relation))
            : $relation;

        // New strategy-based conversions
        $strategy = config('query-builder.convert_relation_table_name_strategy', false);
        if ($tableName) {
            if ($strategy === 'snake_case') {
                $possibleRelatedNames[] = Str::snake($tableName);
            } elseif ($strategy === 'camelCase') {
                $possibleRelatedNames[] = Str::camel($tableName);
            } elseif ($strategy === 'none') {
                $possibleRelatedNames[] = $tableName;
            }
        }

        // Get fields with potential snake_case conversion
        $fields = $this->request->fields();

        if (config('query-builder.convert_field_names_to_snake_case', false)) {
            $fields = $fields->mapWithKeys(fn ($fields, $table) => [
                $table => collect($fields)->map(fn ($field) => Str::snake($field)),
            ]);
        }

        // Find fields for any of the possible table names
        $matchedFields = null;
        foreach ($possibleRelatedNames as $tableName) {
            if ($fields->has($tableName)) {
                $matchedFields = $fields->get($tableName);

                break;
            }
        }

        if (! $matchedFields) {
            return [];
        }

        $matchedFields = $matchedFields->toArray();

        // Validate against allowed fields as in original implementation
        if (! $this->allowedFields instanceof Collection) {
            throw new UnknownIncludedFieldsQuery($matchedFields);
        }

        // Prepend table name if provided (from new implementation)
        if ($tableName !== null) {
            $matchedFields = $this->prependFieldsWithTableName($matchedFields, $tableName);
        }

        return $matchedFields;
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
