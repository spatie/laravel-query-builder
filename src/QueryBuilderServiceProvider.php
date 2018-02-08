<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Request::macro('includes', function ($include = null) {
            $includeParts = $this->query('include');

            if (! is_array($includeParts)) {
                $includeParts = explode(',', strtolower($this->query('include')));
            }

            $includes = collect($includeParts)->filter();

            if (is_null($include)) {
                return $includes;
            }

            return $includes->contains(strtolower($include));
        });

        Request::macro('filters', function ($filter = null) {
            $filterParts = $this->query('filter');

            if (! $filterParts) {
                return collect();
            }

            $filters = collect($filterParts)->filter(function ($filter) {
                return ! is_null($filter);
            });

            $filters = $filters->map(function ($value) {
                if (str_contains($value, ',')) {
                    return explode(',', $value);
                }

                if ($value === 'true') {
                    return true;
                }

                if ($value === 'false') {
                    return false;
                }

                return $value;
            });

            if (is_null($filter)) {
                return $filters;
            }

            return $filters->get(strtolower($filter));
        });

        Request::macro('sort', function ($default = null) {
            return $this->query('sort', $default);
        });

        Request::macro('column', function ($default = null) {
            $columns = $this->query('column', $default);

            if (is_string($columns)) {
                return collect(explode(',', $columns));
            }

            return collect($columns);
        });
      
        Request::macro('sorts', function ($default = null) {
            $sortParts = $this->sort();

            if (! is_array($sortParts)) {
                $sortParts = explode(',', $sortParts);
            }

            $sorts = collect($sortParts)->filter();

            if ($sorts->isNotEmpty()) {
                return $sorts;
            }

            return collect($default)->filter();
        });
    }
}
