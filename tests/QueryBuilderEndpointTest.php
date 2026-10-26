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
    TestModel::factory()->create(['name' => 'Zebra']);
    TestModel::factory()->create(['name' => 'Alpha']);

    Route::get('/test-request', function () {
        return QueryBuilder::for(TestModel::class)
            ->allowedIncludes('relatedModels')
            ->allowedFields('name')
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name')
            ->defaultSort('id')
            ->get();
    });

    $this->getJson("/test-request?{$queryString}")
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.name', 'Zebra')
        ->assertJsonPath('1.name', 'Alpha');
})->with([
    'sort[0][x]=1',
    'include[0][x]=1',
    'fields[test_models][0][x]=1',
    'fields[0][x]=1',
    'filter[name][0][x]=1',
]);

it('preserves valid siblings of malformed query parameters', function (string $fields) {
    TestModel::factory()->create(['name' => 'Zebra']);
    $alpha = TestModel::factory()->create(['name' => 'Alpha']);
    $omega = TestModel::factory()->create(['name' => 'Omega']);
    $alpha->relatedModels()->create(['name' => 'First']);
    $omega->relatedModels()->create(['name' => 'Last']);

    Route::get('/test-request', function () {
        return QueryBuilder::for(TestModel::class)
            ->allowedFields('id', 'name', 'related_models.name', 'related_models.test_model_id')
            ->allowedIncludes('relatedModels')
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name')
            ->get();
    });

    $queryString = 'sort[0][x]=1&sort[1]=-name'
        .'&include[0][x]=1&include[1]=relatedModels'
        .'&filter[name][0][x]=Zeb&filter[name][1]=alp&filter[name][2]=ome'
        ."&{$fields}&fields[related_models][0][x]=1&fields[related_models][1]=name&fields[related_models][2]=test_model_id";

    $this->getJson("/test-request?{$queryString}")
        ->assertOk()
        ->assertExactJson([
            ['id' => $omega->id, 'name' => 'Omega', 'related_models' => [['name' => 'Last', 'test_model_id' => $omega->id]]],
            ['id' => $alpha->id, 'name' => 'Alpha', 'related_models' => [['name' => 'First', 'test_model_id' => $alpha->id]]],
        ]);
})->with([
    'unqualified fields' => 'fields[0][x]=1&fields[1]=id&fields[2]=name',
    'model fields' => 'fields[test_models][0][x]=1&fields[test_models][1]=id&fields[test_models][2]=name',
    'malformed model fields with unqualified siblings' => 'fields[0]=id&fields[1]=name&fields[test_models][0][x]=1',
]);
