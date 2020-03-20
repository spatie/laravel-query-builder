<?php

namespace Spatie\QueryBuilder;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole() && function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/query-builder.php' => config_path('query-builder.php'),
            ], 'config');
        }

        $this->mergeConfigFrom(__DIR__.'/../config/query-builder.php', 'query-builder');
    }

    public function register()
    {
        $this->app->bind(QueryBuilderRequest::class, function ($app) {
            return QueryBuilderRequest::fromRequest($app['request']);
        });
    }

    public function provides()
    {
        return [
            QueryBuilderRequest::class,
        ];
    }
}
