<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Symfony\Component\HttpFoundation\ParameterBag;

class ColumnTest extends TestCase
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
            'fields' => ['test_models' => 'name'],
        ]);

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)->toSql();

        $expected = TestModel::query()->select("{$this->modelTableName}.name")->toSql();

        $this->assertEquals($expected, $queryBuilder);
    }

    /** @test */
    public function it_can_fetch_only_required_columns_when_use_json_request()
    {
        $request = new Request();
        $request->setJson(new ParameterBag([
            'fields' => ['test_models' => 'name'],
        ]));

        $queryBuilder = QueryBuilder::for(TestModel::class, $request)->toSql();

        $expected = TestModel::query()->select("{$this->modelTableName}.name")->toSql();

        $this->assertEquals($expected, $queryBuilder);
    }
}
