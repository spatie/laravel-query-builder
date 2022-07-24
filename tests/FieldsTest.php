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

it('replaces selected columns on the query', function () {
    $query = createQueryFromFieldRequest(['testModel' => 'name,id'])
        ->select(['id', 'is_visible'])
        ->allowedFields(['name', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
    $this->assertStringNotContainsString('is_visible', $expected);
});

it('can fetch specific columns', function () {
    $query = createQueryFromFieldRequest(['testModel' => 'name,id'])
        ->allowedFields(['name', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
});

it('wont fetch a specific column if its not allowed', function () {
    $query = createQueryFromFieldRequest(['testModel' => 'random-column'])->toSql();

    $expected = TestModel::query()->toSql();

    expect($query)->toEqual($expected);
});

it('can fetch sketchy columns if they are allowed fields', function () {
    $query = createQueryFromFieldRequest(['testModel' => 'name->first,id'])
        ->allowedFields(['name->first', 'id'])
        ->toSql();

    $expected = TestModel::query()
        ->select("{$this->modelTableName}.name->first", "{$this->modelTableName}.id")
        ->toSql();

    expect($query)->toEqual($expected);
});

it('guards against not allowed fields', function () {
    $this->expectException(InvalidFieldQuery::class);

    createQueryFromFieldRequest(['testModel' => 'random-column'])
        ->allowedFields('name');
});

it('guards against not allowed fields from an included resource', function () {
    $this->expectException(InvalidFieldQuery::class);

    createQueryFromFieldRequest(['related_models' => 'random_column'])
        ->allowedFields('related_models.name');
});

it('can fetch only requested columns from an included model', function () {
    RelatedModel::create([
        'test_model_id' => $this->model->id,
        'name' => 'related',
    ]);

    $request = new Request([
        'fields' => [
            'testModel' => 'id',
            'relatedModels' => 'name',
        ],
        'include' => ['relatedModels'],
    ]);

    $queryBuilder = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields('relatedModels.name', 'id')
        ->allowedIncludes('relatedModels');

    DB::enableQueryLog();

    $queryBuilder->first()->relatedModels;

    $this->assertQueryLogContains('select `test_models`.`id` from `test_models`');
    $this->assertQueryLogContains('select `id`, `test_model_id`, `name` from `related_models`');
});

it('can fetch requested columns from included models up to two levels deep', function () {
    RelatedModel::create([
        'test_model_id' => $this->model->id,
        'name' => 'related',
    ]);

    $request = new Request([
        'fields' => [
            'testModel' => 'id,name',
            'relatedModels.testModel' => 'id',
        ],
        'include' => ['relatedModels.testModel'],
    ]);

    $result = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields('relatedModels.testModel.id', 'id', 'name')
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
            'testModels' => 'id',
            'relatedModels' => 'name',
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
            'testModel' => 'id',
            'relatedModels' => 'name',
        ],
        'include' => ['relatedModels'],
    ]);

    $this->expectException(UnknownIncludedFieldsQuery::class);

    QueryBuilder::for(TestModel::class, $request)
        ->allowedIncludes('relatedModels');
});

it('can allow specific fields on an included model', function () {
    $request = new Request([
        'fields' => ['relatedModels' => 'id,name'],
        'include' => ['relatedModels'],
    ]);

    $queryBuilder = QueryBuilder::for(TestModel::class, $request)
        ->allowedFields(['relatedModels.id', 'relatedModels.name'])
        ->allowedIncludes('relatedModels');

    DB::enableQueryLog();

    $queryBuilder->first()->relatedModels;

    $this->assertQueryLogContains('select * from `test_models`');
    $this->assertQueryLogContains('select `id`, `test_model_id`, `name` from `related_models`');
});

it('wont use sketchy field requests', function () {
    $request = new Request([
        'fields' => ['testModel' => 'id->"\')from testModel--injection'],
    ]);

    DB::enableQueryLog();

    QueryBuilder::for(TestModel::class, $request)->get();

    $this->assertQueryLogDoesntContain('--injection');
});

it('takes allowed fields for a relation into account', function () {
    $createdRelatedModel = RelatedModel::create([
        'test_model_id' => $this->model->id,
        'name' => 'related',
    ]);

    $request = new Request([
        'fields'  => ['relatedModels' => 'id,name'],
        'include' => ['relatedModels'],
    ]);

    DB::enableQueryLog();

    $result = QueryBuilder::for(TestModel::class, $request)
      ->allowedFields(['relatedModels.id', 'relatedModels.name'])
      ->allowedIncludes('relatedModels')
      ->first();

    $queryLogBefore = DB::getQueryLog();

    expect($result->relatedModels)->toHaveCount(1);
    expect($result->relatedModels->first()->attributesToArray())->toMatchArray(
        $createdRelatedModel->attributesToArray(),
    );
    expect(DB::getQueryLog())->toEqual($queryLogBefore); // Ensure eager loading
});

// Helpers
function createQueryFromFieldRequest(array $fields = []): QueryBuilder
{
    $request = new Request([
        'fields' => $fields,
    ]);

    return QueryBuilder::for(TestModel::class, $request);
}
