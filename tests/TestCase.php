<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelRay\RayServiceProvider;
use Spatie\QueryBuilder\QueryBuilderRequest;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;

class TestCase extends Orchestra
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        QueryBuilderRequest::resetDelimiters();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Spatie\\QueryBuilder\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function setUpDatabase(Application $app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->nullable();
            $table->string('full_name')->nullable();
            $table->double('salary')->nullable();
            $table->boolean('is_visible')->default(true);
        });

        $app['db']->connection()->getSchemaBuilder()->create('append_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('firstname');
            $table->string('lastname');
        });

        $app['db']->connection()->getSchemaBuilder()->create('soft_delete_models', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->string('name');
        });

        $app['db']->connection()->getSchemaBuilder()->create('scope_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        $app['db']->connection()->getSchemaBuilder()->create('related_models', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('test_model_id');
            $table->string('name');
            $table->string('full_name')->nullable();
        });

        $app['db']->connection()->getSchemaBuilder()->create('nested_related_models', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('related_model_id');
            $table->string('name');
        });

        $app['db']->connection()->getSchemaBuilder()->create('pivot_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('test_model_id');
            $table->integer('related_through_pivot_model_id');
            $table->string('location')->nullable();
        });

        $app['db']->connection()->getSchemaBuilder()->create('related_through_pivot_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        $app['db']->connection()->getSchemaBuilder()->create('morph_models', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('parent');
            $table->string('name');
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            // RayServiceProvider::class,
            QueryBuilderServiceProvider::class,
        ];
    }

    protected function assertQueryLogContains(string $partialSql)
    {
        $queryLog = collect(DB::getQueryLog())->pluck('query')->implode('|');

        // Could've used `assertStringContainsString` but we want to support L5.5 with PHPUnit 6.0
        $this->assertTrue(Str::contains($queryLog, $partialSql), "Query log did not contain partial SQL: `{$partialSql}`. Query log: {$queryLog}");
    }

    protected function assertQueryLogDoesntContain(string $partialSql)
    {
        $queryLog = collect(DB::getQueryLog())->pluck('query')->implode('|');

        // Could've used `assertStringContainsString` but we want to support L5.5 with PHPUnit 6.0
        $this->assertFalse(Str::contains($queryLog, $partialSql), "Query log contained partial SQL: `{$partialSql}`");
    }

    public function sortCallback(Builder $query, $descending): void
    {
        $query->orderBy('name', $descending ? 'DESC' : 'ASC');
    }

    public function filterCallback(Builder $query, $value): void
    {
        $query->where('name', $value);
    }
}
