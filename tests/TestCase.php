<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\QueryBuilder\QueryBuilderServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->withFactories(__DIR__.'/factories');
    }

    protected function setUpDatabase(Application $app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
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
        });

        $app['db']->connection()->getSchemaBuilder()->create('related_through_pivot_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
    }

    protected function getPackageProviders($app)
    {
        return [QueryBuilderServiceProvider::class];
    }
}
