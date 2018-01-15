<?php

namespace Spatie\QueryBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // if ($this->app->runningInConsole()) {
        //     $this->publishes([
        //         __DIR__.'/../config/query-builder.php' => config_path('query-builder.php'),
        //     ], 'config');
        // }

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

            if (! is_array($filterParts)) {
                $filterParts = collect(explode(',', strtolower($this->query('filter'))));
            }

            $filters = collect($filterParts)->filter(function ($filter) {
                return ! is_null($filter);
            });

            $filters = $filters->map(function ($value) {
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

            return $filters->contains(strtolower($filter));
        });

        Request::macro('sort', function ($sort = null) {
            return $this->query('sort');
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'query-builder');
    }
}
