<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/query-builder.php' => config_path('query-builder.php'),
            ], 'config');
        }

        $this->mergeConfigFrom(__DIR__.'/../config/query-builder.php', 'query-builder');

        /* @deprecated */
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

        /* @deprecated */
        Request::macro('appends', function ($append = null) {
            $parameter = config('query-builder.parameters.append');
            $appendParts = $this->query($parameter);

            if (! is_array($appendParts)) {
                $appendParts = explode(',', strtolower($this->query($parameter)));
            }

            $appends = collect($appendParts)->filter();

            if (is_null($append)) {
                return $appends;
            }

            return $appends->contains(strtolower($append));
        });

        /* @deprecated */
        Request::macro('filters', function ($filter = null) {
            $filterParts = $this->query(config('query-builder.parameters.filter'), []);

            if (is_string($filterParts)) {
                return collect();
            }

            $filters = collect($filterParts);

            $filtersMapper = function ($value) {
                if (is_array($value)) {
                    return collect($value)->map($this->bindTo($this))->all();
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
            };

            $filters = $filters->map($filtersMapper->bindTo($filtersMapper));

            if (is_null($filter)) {
                return $filters;
            }

            return $filters->get(strtolower($filter));
        });

        /* @deprecated */
        Request::macro('fields', function (): Collection {
            $fieldsParameter = config('query-builder.parameters.fields');

            $fieldsPerTable = collect($this->query($fieldsParameter));

            if ($fieldsPerTable->isEmpty()) {
                return collect();
            }

            return $fieldsPerTable->map(function ($fields) {
                return explode(',', $fields);
            });
        });

        /* @deprecated */
        Request::macro('sort', function ($default = null) {
            return $this->query(config('query-builder.parameters.sort'), $default);
        });

        /* @deprecated */
        Request::macro('sorts', function ($default = null) {
            $sortParts = $this->sort();

            if (! is_array($sortParts)) {
                $sortParts = explode(',', $sortParts);
            }

            $sorts = collect($sortParts)->filter();

            if ($sorts->isNotEmpty()) {
                return $sorts;
            }

            if (! $default instanceof Collection) {
                $default = collect($default);
            }

            return $default->filter();
        });
    }
}
