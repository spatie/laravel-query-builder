<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\UnknownIncludedFieldsQuery;

trait AddsFieldsToQuery
{
    protected ?Collection $allowedFields = null;

    public function allowedFields(string ...$fields): static
    {
        $this->allowedFields = collect($fields)
            ->map(function (string $fieldName) {
                return $this->prependField($fieldName);
            });

        $this->ensureAllFieldsExist();

        $this->addRequestedModelFieldsToQuery();

        return $this;
    }

    protected function addRequestedModelFieldsToQuery(): void
    {
        $modelTableName = $this->getModel()->getTable();

        $fields = $this->request->fields();

        if (! $fields->isEmpty() && config('query-builder.convert_field_names_to_snake_case', false)) {
            $fields = $fields->mapWithKeys(fn ($fields, $table) => [$table => collect($fields)->map(fn ($field) => Str::snake($field))->toArray()]);
        }

        $strategy = config('query-builder.convert_relation_table_name_strategy');

        if ($strategy === 'camelCase') {
            $modelFields = $fields->has(Str::camel($modelTableName)) ? $fields->get(Str::camel($modelTableName)) : $fields->get('_');
        } else {
            $modelFields = $fields->has($modelTableName) ? $fields->get($modelTableName) : $fields->get('_');
        }

        if (empty($modelFields)) {
            return;
        }

        $prependedFields = $this->prependFieldsWithTableName($modelFields, $modelTableName);

        $this->select($prependedFields);
    }

    public function getRequestedFieldsForRelatedTable(string $relation, ?string $tableName = null): array
    {
        $possibleRelatedNames = [
            config('query-builder.convert_relation_names_to_snake_case_plural', true)
                ? Str::plural(Str::snake($relation))
                : $relation,
        ];

        $strategy = config('query-builder.convert_relation_table_name_strategy');

        if ($strategy === 'snake_case' && $tableName) {
            $possibleRelatedNames[] = Str::snake($tableName);
        } elseif ($strategy === 'camelCase' && $tableName) {
            $possibleRelatedNames[] = Str::camel($tableName);
        } elseif ($strategy === 'none') {
            $possibleRelatedNames[] = $tableName;
        }

        $possibleRelatedNames = array_filter($possibleRelatedNames);

        $fields = $this->request->fields()
            ->mapWithKeys(fn ($fields, $table) => [$table => collect($fields)->map(fn ($field) => config('query-builder.convert_field_names_to_snake_case', false) ? Str::snake($field) : $field)])
            ->filter(fn ($value, $table) => in_array($table, $possibleRelatedNames))
            ->first();

        if (! $fields) {
            return [];
        }

        $fields = $fields->toArray();

        if ($tableName !== null) {
            $fields = $this->prependFieldsWithTableName($fields, $tableName);
        }

        if (! $this->allowedFields instanceof Collection) {
            throw new UnknownIncludedFieldsQuery($fields);
        }

        return $fields;
    }

    protected function ensureAllFieldsExist(): void
    {
        $modelTable = $this->getModel()->getTable();

        $requestedFields = $this->request->fields()
            ->map(function ($fields, $model) use ($modelTable) {
                $tableName = $model;

                return $this->prependFieldsWithTableName($fields, $model === '_' ? $modelTable : $tableName);
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
            return $field;
        }

        return "{$table}.{$field}";
    }
}
