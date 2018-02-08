<?php

namespace Spatie\QueryBuilder\Tests;

use Spatie\QueryBuilder\Tests\Models\TestModel;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;


class ColumnTest extends TestCase
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
        $request = new Request(['column' => 'name']);

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)->toSql();

        $expected = TestModel::query()->select('name')->toSql();

        $this->assertEquals($expected, $queryBuilder);
    }
}
