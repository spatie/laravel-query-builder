<?php

namespace Spatie\QueryBuilder\Tests;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Tests\Models\ScopeModel;
use Spatie\QueryBuilder\Tests\Models\SoftDeleteModel;

class QueryBuilderTest extends TestCase
{
    /** @test */
    public function it_will_determine_the_request_when_its_not_given()
    {
        $this->getJson('/test-model?sort=name');

        $builder = QueryBuilder::for(TestModel::class);

        $this->assertEquals([
            'direction' => 'asc',
            'column' => 'name',
        ], $builder->getQuery()->orders[0]);
    }

    /** @test */
    public function it_can_be_given_a_custom_base_query()
    {
        $queryBuilder = QueryBuilder::for(TestModel::where('id', 1));

        $eloquentBuilder = TestModel::where('id', 1);

        $this->assertEquals($eloquentBuilder->toSql(), $queryBuilder->toSql());
    }

    /** @test */
    public function it_can_query_soft_deletes()
    {
        $queryBuilder = QueryBuilder::for(SoftDeleteModel::class);

        $this->models = factory(SoftDeleteModel::class, 5)->create();

        $this->assertCount(5, $queryBuilder->get());

        $this->models[0]->delete();

        $this->assertCount(4, $queryBuilder->get());
        $this->assertCount(5, $queryBuilder->withTrashed()->get());
    }

    /** @test */
    public function it_can_query_global_scopes()
    {
        $queryBuilder = QueryBuilder::for(ScopeModel::class);

        ScopeModel::create(['name' => 'John Doe']);
        ScopeModel::create(['name' => 'test']);

        // Global scope ignores models with name "test"
        $this->assertCount(1, $queryBuilder->get());

        $this->assertCount(2, $queryBuilder->withoutGlobalScopes()->get());
    }

    /** @test */
    public function it_can_query_local_scopes()
    {
        $queryBuilderQuery = QueryBuilder::for(TestModel::class)
            ->named('john')
            ->toSql();

        $expectedQuery = TestModel::query()->where('name', 'john')->toSql();

        $this->assertEquals($expectedQuery, $queryBuilderQuery);
    }
}
