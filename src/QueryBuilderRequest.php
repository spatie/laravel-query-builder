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

    public function includes()
    {
        $parameter = config('query-builder.parameters.include');

        $includeParts = $this->getPartsOfRequest($parameter);

        if (! is_array($includeParts)) {
            $includeParts = explode(',', strtolower($this->getPartsOfRequest($parameter)));
        }

        return collect($includeParts)->filter();
    }

    public function appends()
    {
        $appendParameter = config('query-builder.parameters.append');

        $appendParts = $this->getPartsOfRequest($appendParameter);

        if (! is_array($appendParts)) {
            $appendParts = explode(',', strtolower($appendParts));
        }

        return collect($appendParts)->filter();
    }

    public function filters()
    {
        $filterParameter = config('query-builder.parameters.filter');

        $filterParts = $this->getPartsOfRequest($filterParameter);

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
        $fieldsParameter = config('query-builder.parameters.fields');

        $fieldsPerTable = collect($this->getPartsOfRequest($fieldsParameter));

        if ($fieldsPerTable->isEmpty()) {
            return collect();
        }

        return $fieldsPerTable->map(function ($fields) {
            return explode(',', $fields);
        });
    }

    /**
     * @return array|string|null
     */
    public function sort()
    {
        return $this->getPartsOfRequest(config('query-builder.parameters.sort'));
    }

    public function sorts(): Collection
    {
        $sortParts = $this->sort();

        if (is_string($sortParts)) {
            $sortParts = explode(',', $sortParts);
        }

        return collect($sortParts)->filter();
    }

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

    protected function getPartsOfRequest($parameter)
    {
        if (! empty($this->query($parameter, []))) {
            return $this->query($parameter, []);
        }

        if (! empty($this->json($parameter, []))) {
            return $this->json($parameter, []);
        }

        return [];
    }
}
