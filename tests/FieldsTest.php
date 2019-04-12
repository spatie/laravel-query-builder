<?php

namespace Spatie\QueryBuilder\Tests;

use DB;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Tests\Models\RelatedModel;
use Spatie\QueryBuilder\Exceptions\InvalidColumnName;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;

class FieldsTest extends TestCase
{
    protected $model;
    protected $modelTableName;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = factory(TestModel::class)->create();
        $this->modelTableName = $this->model->getTable();
    }

    /** @test */
    public function it_fetches_all_columns_if_no_field_was_requested()
    {
        $query = QueryBuilder::for(TestModel::class)->toSql();

        $expected = TestModel::query()->toSql();

        $this->assertEquals($expected, $query);
    }

    /** @test */
    public function it_fetches_all_columns_if_no_specific_columns_were_requested()
    {
        $query = QueryBuilder::for(TestModel::class)->allowedFields('id', 'name')->toSql();

        $expected = TestModel::query()->toSql();

        $this->assertEquals($expected, $query);
    }

    /** @test */
    public function it_can_fetch_specific_columns()
    {
        $query = $this
            ->createQueryFromFieldRequest(['test_models' => 'name,id'])
            ->allowedFields(['name', 'id'])
            ->toSql();

        $expected = TestModel::query()
            ->select("{$this->modelTableName}.name", "{$this->modelTableName}.id")
            ->toSql();

        $this->assertEquals($expected, $query);
    }

    /** @test */
    public function it_can_fetch_sketchy_columns_if_they_are_allowed_fields()
    {
        $query = $this
            ->createQueryFromFieldRequest(['test_models' => 'name->first,id'])
            ->allowedFields(['name->first', 'id'])
            ->toSql();

        $expected = TestModel::query()
            ->select("{$this->modelTableName}.name->first", "{$this->modelTableName}.id")
            ->toSql();

        $this->assertEquals($expected, $query);
    }

    /** @test */
    public function it_guards_against_invalid_fields()
    {
        $this->expectException(InvalidFieldQuery::class);

        $this
            ->createQueryFromFieldRequest(['test_models' => 'random-column'])
            ->allowedFields('name');
    }

    /** @test */
    public function it_guards_against_invalid_fields_from_an_included_resource()
    {
        $this->expectException(InvalidFieldQuery::class);

        $this
            ->createQueryFromFieldRequest(['related_models' => 'random_column'])
            ->allowedFields('related_models.name');
    }

    /** @test */
    public function it_can_fetch_only_requested_columns_from_an_included_model()
    {
        RelatedModel::create([
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

    /** @test */
    public function it_can_allow_specific_fields_on_an_included_model()
    {
        $request = new Request([
            'fields' => ['related_models' => 'id,name'],
            'include' => ['related-models'],
        ]);

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)
            ->allowedIncludes('related-models')
            ->allowedFields(['related_models.id', 'related_models.name']);

        DB::enableQueryLog();

        $queryBuilder->first()->relatedModels;

        $this->assertQueryLogContains('select * from "test_models"');
        $this->assertQueryLogContains('select "id", "name" from "related_models"');
    }

    /** @test */
    public function it_wont_use_sketchy_field_requests()
    {
        $request = new Request([
            'fields' => ['test_models' => 'id->"\')from test_models--injection'],
        ]);

        $this->expectException(InvalidColumnName::class);

        DB::enableQueryLog();

        QueryBuilder::for(TestModel::class, $request)->get();

        $this->assertQueryLogDoesntContain('--injection');
    }

    protected function createQueryFromFieldRequest(array $fields): QueryBuilder
    {
        $request = new Request([
            'fields' => $fields,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
