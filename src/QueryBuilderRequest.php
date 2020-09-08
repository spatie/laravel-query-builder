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
        return static::createFrom($request, new self());
    }

    public function includes(): Collection
    {
        $includeParameterName = config('query-builder.parameters.include');

        $includeParts = $this->query($includeParameterName);

        if (! is_array($includeParts)) {
            $includeParts = explode(static::getIncludesArrayValueDelimiter(), $this->query($includeParameterName));
        }

        return collect($includeParts)
            ->filter()
            ->map([Str::class, 'camel']);
    }

    public function appends(): Collection
    {
        $appendParameterName = config('query-builder.parameters.append');

        $appendParts = $this->query($appendParameterName);

        if (! is_array($appendParts)) {
            $appendParts = explode(static::getAppendsArrayValueDelimiter(), strtolower($appendParts));
        }

        return collect($appendParts)->filter();
    }

    public function fields(): Collection
    {
        $fieldsParameterName = config('query-builder.parameters.fields');

        $fieldsPerTable = collect($this->query($fieldsParameterName));

        if ($fieldsPerTable->isEmpty()) {
            return collect();
        }

        return $fieldsPerTable->map(function ($fields) {
            return explode(static::getFieldsArrayValueDelimiter(), $fields);
        });
    }

    public function sorts(): Collection
    {
        $sortParameterName = config('query-builder.parameters.sort');

        $sortParts = $this->query($sortParameterName);

        if (is_string($sortParts)) {
            $sortParts = explode(static::getSortsArrayValueDelimiter(), $sortParts);
        }

        return collect($sortParts)->filter();
    }

    public function filters(): Collection
    {
        $filterParameterName = config('query-builder.parameters.filter');

        $filterParts = $this->query($filterParameterName, []);

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
     * @return array|bool
     */
    protected function getFilterValue($value)
    {
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
}
