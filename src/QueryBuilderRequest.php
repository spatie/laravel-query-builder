<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class QueryBuilderRequest extends Request
{
    public static function fromRequest(Request $request): self
    {
        return static::createFrom($request, new self());
    }

    public function includes(): Collection
    {
        $includeParameterName = config('query-builder.parameters.include');

        $includeParts = $this->query($includeParameterName);

        if (! is_array($includeParts)) {
            $includeParts = explode(',', $this->query($includeParameterName));
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
            $appendParts = explode(',', strtolower($appendParts));
        }

        return collect($appendParts)->filter();
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

    public function fields(): Collection
    {
        $fieldsParameterName = config('query-builder.parameters.fields');

        $fieldsPerTable = collect($this->query($fieldsParameterName));

        if ($fieldsPerTable->isEmpty()) {
            return collect();
        }

        return $fieldsPerTable->map(function ($fields) {
            return explode(',', $fields);
        });
    }

    public function sorts(): Collection
    {
        $sortParameterName = config('query-builder.parameters.sort');

        $sortParts = $this->query($sortParameterName);

        if (is_string($sortParts)) {
            $sortParts = explode(',', $sortParts);
        }

        return collect($sortParts)->filter();
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

        if (Str::contains($value, ',')) {
            return explode(',', $value);
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }
}
