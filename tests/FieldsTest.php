<?php

namespace Spatie\QueryBuilder\Tests;

use DB;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Tests\Models\RelatedModel;
use Spatie\QueryBuilder\Exceptions\InvalidFieldsQuery;

class FieldsTest extends TestCase
{
    protected $model;
    protected $modelTableName;

    public function setUp()
    {
        parent::setUp();

        $this->model = factory(TestModel::class)->create();
        $this->modelTableName = $this->model->getTable();
    }

    /** @test */
    public function it_can_fetch_all_columns_if_none_is_given()
    {
        $queryBuilder = QueryBuilder::for(TestModel::class)->toSql();

        $expected = TestModel::query()->select("{$this->modelTableName}.*")->toSql();

        $this->assertEquals($expected, $queryBuilder);
    }

    /** @test */
    public function it_can_fetch_only_required_columns()
    {
        $queryBuilder = $this->createQueryFromFieldRequest(['test_models' => 'name,id'])->allowedFields(['name', 'id'])->toSql();
        $expected = TestModel::query()
                             ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
                             ->toSql();

        $this->assertEquals($expected, $queryBuilder);
    }

    /** @test */
    public function it_guards_against_invalid_fields()
    {
        $this->expectException(InvalidFieldsQuery::class);

        $this
            ->createQueryFromFieldRequest(['test_models' => 'random-column'])
            ->allowedFields('name');
    }

    /** @test */
    public function it_can_fetch_only_required_columns_from_an_included_model()
    {
        $relatedModel = RelatedModel::create([
            'test_model_id' => $this->model->id,
            'name' => 'related',
        ]);

        $request = new Request([
            'fields' => [
                'test_models' => 'id',
                'related_models' => 'name',
            ],
            'include' => ['related-models'],
        ]);

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)->allowedIncludes('related-models');

        DB::enableQueryLog();

        $queryBuilder->first()->relatedModels;

        $this->assertQueryLogContains('select "test_models"."id" from "test_models"');
        $this->assertQueryLogContains('select "name" from "related_models"');
    }

    protected function createQueryFromFieldRequest(array $fields): QueryBuilder
    {
        $request = new Request([
            'fields' => $fields,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }

    protected function assertQueryLogContains(string $partialSql)
    {
        $queryLog = collect(DB::getQueryLog())->pluck('query')->implode('|');

        $this->assertContains($partialSql, $queryLog);
    }
}
