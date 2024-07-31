<?php

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

it('can instantiate the query builder and filter the query for an actual api request', function () {
    TestModel::factory()->create();

    config(['query-builder.parameters.filter' => null]);

    Route::get('/test-request', function () {
        return QueryBuilder::for(TestModel::class)
            ->allowedIncludes(['group', 'incidents'])
            ->allowedFilters(['name', 'status', 'enabled'])
            ->allowedSorts(['name', 'order', 'id'])
            ->simplePaginate(request('per_page', 15));
    });

    $response = $this->getJson('/test-request');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});
