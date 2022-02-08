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
    
    private static $parameterNames;

    public static function setArrayValueDelimiter(string $delimiter): void
    {
        static::$filterArrayValueDelimiter = $delimiter;
        static::$includesArrayValueDelimiter = $delimiter;
        static::$appendsArrayValueDelimiter = $delimiter;
        static::$fieldsArrayValueDelimiter = $delimiter;
        static::$sortsArrayValueDelimiter = $delimiter;
    }

    public static function fromRequest(Request $request, $parameterNames = []): self
    {
        static::setParameterNames($parameterNames);
        return static::createFrom($request, new self());
    }

    public function includes(): Collection
    {
        $includeParameterName = static::getParameterNames()['include'];
        
        $includeParts = $this->getRequestData($includeParameterName);

        if (! is_array($includeParts)) {
            $includeParts = explode(static::getIncludesArrayValueDelimiter(), $this->getRequestData($includeParameterName));
        }

        return collect($includeParts)->filter();
    }

    public function appends(): Collection
    {
        $appendParameterName = static::getParameterNames()['append'];
        
        $appendParts = $this->getRequestData($appendParameterName);

        if (! is_array($appendParts) && ! is_null($appendParts)) {
            $appendParts = explode(static::getAppendsArrayValueDelimiter(), $appendParts);
        }

        return collect($appendParts)->filter();
    }

    public function fields(): Collection
    {
        $fieldsParameterName = static::getParameterNames()['fields'];

        $fieldsPerTable = collect($this->getRequestData($fieldsParameterName));

        if ($fieldsPerTable->isEmpty()) {
            return collect();
        }

        return $fieldsPerTable->map(function ($fields) {
            return explode(static::getFieldsArrayValueDelimiter(), $fields);
        });
    }

    public function sorts(): Collection
    {
        $sortParameterName = static::getParameterNames()['sort'];

        $sortParts = $this->getRequestData($sortParameterName);

        if (is_string($sortParts)) {
            $sortParts = explode(static::getSortsArrayValueDelimiter(), $sortParts);
        }

        return collect($sortParts)->filter();
    }

    public function filters(): Collection
    {
        $filterParameterName = static::getParameterNames()['filter'];
        
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

    protected function getRequestData(?string $key = null, $default = null)
    {
        if (config('query-builder.request_data_source') === 'body') {
            return $this->input($key, $default);
        }

        return $this->query($key, $default);
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
    
    public static function setParameterNames(array $parameterNames = []): void
    {
        static::$parameterNames = array_merge(
            config('query-builder.parameters'),
            $parameterNames
        );
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
    
    public static function getParameterNames(): array
    {
        return static::$parameterNames;
    }
}
