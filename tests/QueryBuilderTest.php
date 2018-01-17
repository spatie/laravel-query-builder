<?php

namespace Spatie\QueryBuilder\Tests;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;

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
}
