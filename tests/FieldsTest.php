<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Exceptions\AllowedFieldsMustBeCalledBeforeAllowedIncludes;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\UnknownIncludedFieldsQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\RelatedModel;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

beforeEach(function () {
    $this->model = TestModel::factory()->create();

    $this->modelTableName = $this->model->getTable();
});

it('fetches all columns if no field was requested', function () {
    $query = QueryBuilder::for(TestModel::class)->toSql();

    $expected = TestModel::query()->toSql();

    expect($query)->toEqual($expected);
});

it('fetches all columns if no field was requested but allowed fields were specified', function () {
    $query = QueryBuilder::for(TestModel::class)->allowedFields('id', 'name')->toSql();

    $expected = TestModel::query()->toSql();

    expect($query)->toEqual($expected);
});

it('replaces selected array columns on the query', function () {
    $query = createQueryFromFieldRequest(['test_models' => 'name,id'])
        ->select(['id', 'is_visible'])
        ->allowedFields(['name', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
    $this->assertStringNotContainsString('is_visible', $expected);
});

it('replaces selected string columns on the query', function () {
    $query = createQueryFromFieldRequest('name,id')
        ->select(['id', 'is_visible'])
        ->allowedFields(['name', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
    $this->assertStringNotContainsString('is_visible', $expected);
});

it('can fetch specific array columns', function () {
    $query = createQueryFromFieldRequest(['test_models' => 'name,id'])
        ->allowedFields(['name', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
});

it('can fetch specific string columns', function () {
    $query = createQueryFromFieldRequest('name,id')
        ->allowedFields(['name', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
});

it('wont fetch a specific array column if its not allowed', function () {
    $query = createQueryFromFieldRequest(['test_models' => 'random-column'])->toSql();

    $expected = TestModel::query()->toSql();

    expect($query)->toEqual($expected);
});

it('wont fetch a specific string column if its not allowed', function () {
    $query = createQueryFromFieldRequest('random-column')->toSql();

    $expected = TestModel::query()->toSql();

    expect($query)->toEqual($expected);
});

it('can fetch sketchy array columns if they are allowed fields', function () {
    $query = createQueryFromFieldRequest(['test_models' => 'name->first,id'])
        ->allowedFields(['name->first', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name->first", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
});

it('can fetch sketchy string columns if they are allowed fields', function () {
    $query = createQueryFromFieldRequest('name->first,id')
        ->allowedFields(['name->first', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name->first", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
});

it('guards against not allowed array fields', function () {
    $this->expectException(InvalidFieldQuery::class);

    createQueryFromFieldRequest(['test_models' => 'random-column'])
        ->allowedFields('name');
});

it('guards against not allowed string fields', function () {
    $this->expectException(InvalidFieldQuery::class);

    createQueryFromFieldRequest('random-column')
        ->allowedFields('name');
});

it('guards against not allowed array fields from an included resource', function () {
    $this->expectException(InvalidFieldQuery::class);

    createQueryFromFieldRequest(['related_models' => 'random_column'])
        ->allowedFields('related_models.name');
});

it('guards against not allowed string fields from an included resource', function () {
    $this->expectException(InvalidFieldQuery::class);

    createQueryFromFieldRequest('related_models.random_column')
        ->allowedFields('related_models.name');
});

it('can fetch only requested array columns from an included model', function () {
    RelatedModel::create([
        'test_model_id' => $this->model->id,
        'name' => 'related',
    ]);

    $request = new Request([
        'fields' => [
            'test_models' => 'id',
            'related_models' => 'name',
        ],
        'include' => ['relatedModels'],
    ]);

    $queryBuilder = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields('related_models.name', 'id')
        ->allowedIncludes('relatedModels');

    DB::enableQueryLog();

    $queryBuilder->first()->relatedModels;

    $this->assertQueryLogContains('select `test_models`.`id` from `test_models`');
    $this->assertQueryLogContains('select `related_models`.`name` from `related_models`');
});

it('can fetch only requested string columns from an included model', function () {
    RelatedModel::create([
        'test_model_id' => $this->model->id,
        'name' => 'related',
    ]);

    $request = new Request([
        'fields' => 'id,related_models.name',
        'include' => ['relatedModels'],
    ]);

    $queryBuilder = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields('related_models.name', 'id')
        ->allowedIncludes('relatedModels');

    DB::enableQueryLog();

    $queryBuilder->first()->relatedModels;

    $this->assertQueryLogContains('select `test_models`.`id` from `test_models`');
    $this->assertQueryLogContains('select `related_models`.`name` from `related_models`');
});

it('can fetch only requested string columns from an included belongs to many model', function () {
    TestModel::first()->relatedThroughPivotModels()->create([
        'name' => 'related',
    ]);

    $request = new Request([
        'fields' => 'id,related_through_pivot_models.id,related_through_pivot_models.name',
        'include' => ['relatedThroughPivotModels'],
    ]);

    $queryBuilder = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields('id', 'related_through_pivot_models.id', 'related_through_pivot_models.name')
        ->allowedIncludes('relatedThroughPivotModels');

    DB::enableQueryLog();

    $queryBuilder->first()->relatedThroughPivotModels;

    $this->assertQueryLogContains('select `test_models`.`id` from `test_models`');
    $this->assertQueryLogContains('select `related_through_pivot_models`.`id`, `related_through_pivot_models`.`name`, `pivot_models`.`test_model_id` as `pivot_test_model_id`, `pivot_models`.`related_through_pivot_model_id` as `pivot_related_through_pivot_model_id` from `related_through_pivot_models` inner join `pivot_models` on `related_through_pivot_models`.`id` = `pivot_models`.`related_through_pivot_model_id` where `pivot_models`.`test_model_id` in (');
});

it('can fetch requested array columns from included models up to two levels deep', function () {
    RelatedModel::create([
        'test_model_id' => $this->model->id,
        'name' => 'related',
    ]);

    $request = new Request([
        'fields' => [
            'test_models' => 'id,name',
            'related_models.test_models' => 'id',
        ],
        'include' => ['relatedModels.testModel'],
    ]);

    $result = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields('related_models.test_models.id', 'id', 'name')
        ->allowedIncludes('relatedModels.testModel')
        ->first();

    $this->assertArrayHasKey('name', $result);

    expect($result->relatedModels->first()->testModel->toArray())->toEqual(['id' => $this->model->id]);
});

it('can fetch requested string columns from included models up to two levels deep', function () {
    RelatedModel::create([
        'test_model_id' => $this->model->id,
        'name' => 'related',
    ]);

    $request = new Request([
        'fields' => 'id,name,related_models.test_models.id',
        'include' => ['relatedModels.testModel'],
    ]);

    $result = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields('related_models.test_models.id', 'id', 'name')
        ->allowedIncludes('relatedModels.testModel')
        ->first();

    $this->assertArrayHasKey('name', $result);

    expect($result->relatedModels->first()->testModel->toArray())->toEqual(['id' => $this->model->id]);
});

it('throws an exception when calling allowed includes before allowed fields', function () {
    $this->expectException(AllowedFieldsMustBeCalledBeforeAllowedIncludes::class);

    createQueryFromFieldRequest()
        ->allowedIncludes('related-models')
        ->allowedFields('name');
});

it('throws an exception when calling allowed includes before allowed fields but with requested fields', function () {
    $request = new Request([
        'fields' => [
            'test_models' => 'id',
            'related_models' => 'name',
        ],
        'include' => ['relatedModels'],
    ]);

    $this->expectException(UnknownIncludedFieldsQuery::class);

    QueryBuilder::for(TestModel::class, $request)
        ->allowedIncludes('relatedModels')
        ->allowedFields('name');
});

it('throws an exception when requesting fields for an allowed included without any allowed fields', function () {
    $request = new Request([
        'fields' => [
            'test_models' => 'id',
            'related_models' => 'name',
        ],
        'include' => ['relatedModels'],
    ]);

    $this->expectException(UnknownIncludedFieldsQuery::class);

    QueryBuilder::for(TestModel::class, $request)
        ->allowedIncludes('relatedModels');
});

it('can allow specific fields on an included model', function () {
    $request = new Request([
        'fields' => ['related_models' => 'id,name'],
        'include' => ['relatedModels'],
    ]);

    $queryBuilder = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields(['related_models.id', 'related_models.name'])
        ->allowedIncludes('relatedModels');

    DB::enableQueryLog();

    $queryBuilder->first()->relatedModels;

    $this->assertQueryLogContains('select * from `test_models`');
    $this->assertQueryLogContains('select `related_models`.`id`, `related_models`.`name` from `related_models`');
});

it('wont use sketchy field requests', function () {
    $request = new Request([
        'fields' => ['test_models' => 'id->"\')from test_models--injection'],
    ]);

    DB::enableQueryLog();

    QueryBuilder::for(TestModel::class, $request)->get();

    $this->assertQueryLogDoesntContain('--injection');
});

// Helpers
function createQueryFromFieldRequest(array|string $fields = []): QueryBuilder
{
    $request = new Request([
        'fields' => $fields,
    ]);

    return QueryBuilder::for(TestModel::class, $request);
}
