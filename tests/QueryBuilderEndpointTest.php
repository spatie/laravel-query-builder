<?php

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

it('can instantiate the query builder and filter the query for an actual api request', function () {
    TestModel::factory()->create();

    config(['query-builder.parameters.filter' => null]);

    Route::get('/test-request', function () {
        return QueryBuilder::for(TestModel::class)
            ->allowedIncludes('group', 'incidents')
            ->allowedFilters('name', 'status', 'enabled')
            ->allowedSorts('name', 'order', 'id')
            ->simplePaginate(request('per_page', 15));
    });

    $response = $this->getJson('/test-request');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('ignores nested arrays in query parameters', function (string $queryString) {
    TestModel::factory()->create();

    Route::get('/test-request', function () {
        return QueryBuilder::for(TestModel::class)
            ->allowedIncludes('relatedModels')
            ->allowedFields('name')
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name')
            ->get();
    });

    $this->getJson("/test-request?{$queryString}")->assertOk();
})->with([
    'sort[0][x]=1',
    'include[0][x]=1',
    'fields[test_models][0][x]=1',
    'fields[0][x]=1',
    'filter[name][0][x]=1',
]);
