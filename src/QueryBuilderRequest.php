<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Mappings\Column;

class QueryBuilderRequest extends Request
{
    protected static string $includesArrayValueDelimiter = ',';

    protected static string $appendsArrayValueDelimiter = ',';

    protected static string $fieldsArrayValueDelimiter = ',';

    protected static string $sortsArrayValueDelimiter = ',';

    protected static string $filterArrayValueDelimiter = ',';

    public static function setArrayValueDelimiter(string $delimiter): void
    {
        static::$filterArrayValueDelimiter = $delimiter;
        static::$includesArrayValueDelimiter = $delimiter;
        static::$appendsArrayValueDelimiter = $delimiter;
        static::$fieldsArrayValueDelimiter = $delimiter;
        static::$sortsArrayValueDelimiter = $delimiter;
    }

    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new static());
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

        $fieldsPerTable = collect(is_string($fieldsData) ? explode(static::getFieldsArrayValueDelimiter(), $fieldsData) : $fieldsData);

        $data = $this->getRequestData($fieldsParameterName);

        if (! $data) {
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

        return collect(explode(static::getFieldsArrayValueDelimiter(), $data))->mapInto(Column::class);
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

    public static function setIncludesArrayValueDelimiter(string $includesArrayValueDelimiter): void
    {
        static::$includesArrayValueDelimiter = $includesArrayValueDelimiter;
    }

    public static function setAppendsArrayValueDelimiter(string $appendsArrayValueDelimiter): void
    {
        static::$appendsArrayValueDelimiter = $appendsArrayValueDelimiter;
    }

    public static function setFieldsArrayValueDelimiter(string $fieldsArrayValueDelimiter): void
    {
        static::$fieldsArrayValueDelimiter = $fieldsArrayValueDelimiter;
    }

    public static function setSortsArrayValueDelimiter(string $sortsArrayValueDelimiter): void
    {
        static::$sortsArrayValueDelimiter = $sortsArrayValueDelimiter;
    }

    public static function setFilterArrayValueDelimiter(string $filterArrayValueDelimiter): void
    {
        static::$filterArrayValueDelimiter = $filterArrayValueDelimiter;
    }

    public static function getIncludesArrayValueDelimiter(): string
    {
        return static::$includesArrayValueDelimiter;
    }

    public static function getAppendsArrayValueDelimiter(): string
    {
        return static::$appendsArrayValueDelimiter;
    }

    public static function getFieldsArrayValueDelimiter(): string
    {
        return static::$fieldsArrayValueDelimiter;
    }

    public static function getSortsArrayValueDelimiter(): string
    {
        return static::$sortsArrayValueDelimiter;
    }

    public static function getFilterArrayValueDelimiter(): string
    {
        return static::$filterArrayValueDelimiter;
    }

    public static function resetDelimiters(): void
    {
        self::$includesArrayValueDelimiter = ',';
        self::$appendsArrayValueDelimiter = ',';
        self::$fieldsArrayValueDelimiter = ',';
        self::$sortsArrayValueDelimiter = ',';
        self::$filterArrayValueDelimiter = ',';
    }
}
