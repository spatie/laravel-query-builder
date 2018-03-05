<?php

namespace Spatie\QueryBuilder\Tests;

use DB;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\RelatedModel;
use Spatie\QueryBuilder\Tests\Models\TestModel;

class FieldsTest extends TestCase
{
    protected $model;

    public function setUp()
    {
        parent::setUp();

        $this->model = factory(TestModel::class)->create();
    }

    /** @test */
    public function it_can_fetch_all_columns_if_none_is_given()
    {
        $queryBuilder = QueryBuilder::for(TestModel::class)->toSql();

        $expected = TestModel::query()->toSql();

        $this->assertEquals($expected, $queryBuilder);
    }

    /** @test */
    public function it_can_fetch_only_required_columns()
    {
        $request = new Request([
            'fields' => ['test_models' => 'name,id'],
        ]);

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)->toSql();

        $expected = TestModel::query()->select('name', 'id')->toSql();

        $this->assertEquals($expected, $queryBuilder);
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
                'related_models' => 'name'
            ],
            'include' => ['related-models'],
        ]);

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)->allowedIncludes('related-models');

        DB::enableQueryLog();

        $queryBuilder->first()->relatedModels;

        $this->assertQueryLogContains('select "id" from "test_models"');
        $this->assertQueryLogContains('select "name" from "related_models"');
    }

    protected function assertQueryLogContains(string $partialSql)
    {
        $queryLog = collect(DB::getQueryLog())->pluck('query')->implode('|');

        $this->assertContains($partialSql, $queryLog);
    }
}
