<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryBuilderRequest extends Request
{
    private static $includesArrayValueDelimiter = ',';

    private static $appendsArrayValueDelimiter = ',';

    private static $fieldsArrayValueDelimiter = ',';

    private static $sortsArrayValueDelimiter = ',';

    private static $filterArrayValueDelimiter = ',';

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

        if (is_string($includeParts)) {
            $includeParts = explode(static::getIncludesArrayValueDelimiter(), $includeParts);
        }

        return collect($includeParts)->filter();
    }

    public function appends(): Collection
    {
        $appendParameterName = config('query-builder.parameters.append', 'append');

        $appendParts = $this->getRequestData($appendParameterName);

        if (! is_array($appendParts) && ! is_null($appendParts)) {
            $appendParts = explode(static::getAppendsArrayValueDelimiter(), $appendParts);
        }

        return collect($appendParts)->filter();
    }

    public function fields(): Collection
    {
        $fieldsParameterName = config('query-builder.parameters.fields', 'fields');
        $fieldsData = $this->getRequestData($fieldsParameterName);

        $fieldsPerTable = collect(is_string($fieldsData) ? explode(static::getFieldsArrayValueDelimiter(), $fieldsData) : $fieldsData);

        if ($fieldsPerTable->isEmpty()) {
            return collect();
        }

        $fields = [];

        $fieldsPerTable->each(function ($tableFields, $model) use (&$fields) {
            if (is_numeric($model)) {
                // If the field is in dot notation, we'll grab the table without the field.
                // If the field isn't in dot notation we want the base table. We'll use `_` and replace it later.
                $model = Str::contains($tableFields, '.') ? Str::beforeLast($tableFields, '.') : '_';
            }

            if (! isset($fields[$model])) {
                $fields[$model] = [];
            }

            // If the field is in dot notation, we'll grab the field without the tables:
            $tableFields = array_map(function (string $field) {
                return Str::afterLast($field, '.');
            }, explode(static::getFieldsArrayValueDelimiter(), $tableFields));

            $fields[$model] = array_merge($fields[$model], $tableFields);
        });

        return collect($fields);
    }

    public function sorts(): Collection
    {
        $sortParameterName = config('query-builder.parameters.sort', 'sort');

        $sortParts = $this->getRequestData($sortParameterName);

        if (is_string($sortParts)) {
            $sortParts = explode(static::getSortsArrayValueDelimiter(), $sortParts);
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

    /**
     * @param $value
     *
     * @return array|bool|null
     */
    protected function getFilterValue($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value)->map(function ($valueValue) {
                return $this->getFilterValue($valueValue);
            })->all();
        }

        if (Str::contains($value, static::getFilterArrayValueDelimiter())) {
            return explode(static::getFilterArrayValueDelimiter(), $value);
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }

    protected function getRequestData(?string $key = null, $default = null)
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
