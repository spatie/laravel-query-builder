<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;

class ColumnTest extends TestCase
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
        $request = new Request([
            'fields' => ['test_models' => 'name'],
        ]);

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)->toSql();

        $expected = TestModel::query()->select("{$this->modelTableName}.name")->toSql();

        $this->assertEquals($expected, $queryBuilder);
    }
}
