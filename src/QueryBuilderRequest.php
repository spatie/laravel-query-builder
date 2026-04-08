<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryBuilderRequest extends Request
{
    public static function fromRequest(Request $request): static
    {
        return static::createFrom($request, new static());
    }

    protected function toParameterArray(mixed $parts): array
    {
        if (is_array($parts)) {
            return $parts;
        }

        if (is_null($parts) || $parts === '') {
            return [];
        }

        $delimiter = $this->delimiter();

        if ($delimiter === '') {
            return [(string) $parts];
        }

        return explode($delimiter, (string) $parts);
    }

    public function includes(): Collection
    {
        $includeParameterName = config('query-builder.parameters.include', 'include');

        $includeParts = $this->getRequestData($includeParameterName);

        return collect($this->toParameterArray($includeParts))->filter();
    }

    public function appends(): Collection
    {
        $appendParameterName = config('query-builder.parameters.append', 'append');

        $appendParts = $this->getRequestData($appendParameterName);

        return collect($this->toParameterArray($appendParts))->filter();
    }

    public function fields(): Collection
    {
        $fieldsParameterName = config('query-builder.parameters.fields', 'fields');
        $fieldsData = $this->getRequestData($fieldsParameterName);

        $fieldsPerTable = collect($this->toParameterArray($fieldsData));

        if ($fieldsPerTable->isEmpty()) {
            return collect();
        }

        $fields = [];

        $fieldsPerTable->each(function ($tableFields, $model) use (&$fields) {
            if (is_numeric($model)) {
                $model = Str::contains($tableFields, '.') ? Str::beforeLast($tableFields, '.') : '_';
            }

            if (! isset($fields[$model])) {
                $fields[$model] = [];
            }

            $tableFields = array_map(function (string $field) {
                return Str::afterLast($field, '.');
            }, $this->toParameterArray($tableFields));

            $fields[$model] = array_merge($fields[$model], $tableFields);
        });

        return collect($fields);
    }

    public function sorts(): Collection
    {
        $sortParameterName = config('query-builder.parameters.sort', 'sort');

        $sortParts = $this->getRequestData($sortParameterName);

        return collect($this->toParameterArray($sortParts))->filter();
    }

    public function filters(): Collection
    {
        $filterParameterName = config('query-builder.parameters.filter', 'filter');

        $filterParts = $this->getRequestData($filterParameterName, []);

        if (is_string($filterParts)) {
            return collect();
        }

        $filters = collect($filterParts);

        return $filters->map(function ($value) {
            return $this->getFilterValue($value);
        });
    }

    protected function getFilterValue(mixed $value): mixed
    {
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value)->map(function ($valueValue) {
                return $this->getFilterValue($valueValue);
            })->all();
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }

    protected function getRequestData(?string $key = null, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    protected function delimiter(): string
    {
        return config('query-builder.delimiter', ',');
    }
}
