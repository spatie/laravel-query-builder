<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryBuilderRequest extends Request
{
    protected static bool $filterValueSplittingEnabled = true;

    public static function fromRequest(Request $request): static
    {
        return static::createFrom($request, new static());
    }

    public static function disableFilterValueSplitting(): void
    {
        static::$filterValueSplittingEnabled = false;
    }

    public static function enableFilterValueSplitting(): void
    {
        static::$filterValueSplittingEnabled = true;
    }

    public static function filterValueSplittingEnabled(): bool
    {
        return static::$filterValueSplittingEnabled;
    }

    public function includes(): Collection
    {
        $includeParameterName = config('query-builder.parameters.include', 'include');

        $includeParts = $this->getRequestData($includeParameterName);

        if (is_string($includeParts)) {
            $includeParts = explode($this->delimiter(), $includeParts);
        }

        return collect($includeParts)->filter();
    }

    public function appends(): Collection
    {
        $appendParameterName = config('query-builder.parameters.append', 'append');

        $appendParts = $this->getRequestData($appendParameterName);

        if (! is_array($appendParts) && ! is_null($appendParts)) {
            $appendParts = explode($this->delimiter(), $appendParts);
        }

        return collect($appendParts)->filter();
    }

    public function fields(): Collection
    {
        $fieldsParameterName = config('query-builder.parameters.fields', 'fields');
        $fieldsData = $this->getRequestData($fieldsParameterName);

        $fieldsPerTable = collect(is_string($fieldsData) ? explode($this->delimiter(), $fieldsData) : $fieldsData);

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
            }, explode($this->delimiter(), $tableFields));

            $fields[$model] = array_merge($fields[$model], $tableFields);
        });

        return collect($fields);
    }

    public function sorts(): Collection
    {
        $sortParameterName = config('query-builder.parameters.sort', 'sort');

        $sortParts = $this->getRequestData($sortParameterName);

        if (is_string($sortParts)) {
            $sortParts = explode($this->delimiter(), $sortParts);
        }

        return collect($sortParts)->filter();
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
