<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/query-builder.php' => config_path('query-builder.php')
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/query-builder.php', 'query-builder');

        Request::macro('includes', function ($include = null) {
            $parameter = config('query-builder.parameters.include');
            $includeParts = $this->query($parameter);

            if (! is_array($includeParts)) {
                $includeParts = explode(',', strtolower($this->query($parameter)));
            }

            $includes = collect($includeParts)->filter();

            if (is_null($include)) {
                return $includes;
            }

            return $includes->contains(strtolower($include));
        });

        Request::macro('filters', function ($filter = null) {
            $filterParts = $this->query(
                config('query-builder.parameters.filter')
            );

            if (! $filterParts) {
                return collect();
            }

            $filters = collect($filterParts)->filter(function ($filter) {
                return ! is_null($filter);
            });

            $filtersMapper = function ($value) {
                if (is_array($value)) {
                    return collect($value)->map($this)->all();
                }

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
            };

            $filters = $filters->map($filtersMapper->bindTo($filtersMapper));

            if (is_null($filter)) {
                return $filters;
            }

            return $filters->get(strtolower($filter));
        });

        Request::macro('sort', function ($default = null) {
            return $this->query(config('query-builder.parameters.sort'), $default);
        });

        Request::macro('fields', function ($default = null) {
            return collect($this->query(config('query-builder.parameters.fields'), $default));
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
