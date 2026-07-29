<?php

namespace Spatie\QueryBuilder;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QueryBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-query-builder')
            ->hasConfigFile();
    }

    public function registeringPackage(): void
    {
        $this->app->bind(QueryBuilderRequest::class, function ($app) {
            return QueryBuilderRequest::fromRequest($app['request']);
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            QueryBuilderRequest::class,
        ];
    }
}
