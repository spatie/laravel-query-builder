<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;

class SearchTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp()
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 5)->create();
    }

    /** @test */
    public function it_does_not_require_search_query()
    {
        $models = QueryBuilder::for(TestModel::class, new Request())
            ->get();

        $this->assertCount(TestModel::count(), $models);
    }

    /** @test */
    public function it_can_search_by_name()
    {
        $model1 = TestModel::create(['name' => 'abc']);
        $model2 = TestModel::create(['name' => 'def']);

        $models = $this->createQueryFromSearchRequest('abc')
            ->allowedSearches('name')
            ->get();

        $this->assertCount(1, $models);
        $this->assertEquals([$model1->id], $models->pluck('id')->all());
    }

    /** @test */
    public function it_can_search_when_an_array_is_passed_in_allowed_searches()
    {
        $model1 = TestModel::create(['name' => 'abc']);
        $model2 = TestModel::create(['name' => 'def']);

        $models = $this->createQueryFromSearchRequest('abc')
            ->allowedSearches(['name'])
            ->get();

        $this->assertCount(1, $models);
        $this->assertEquals([$model1->id], $models->pluck('id')->all());
    }

    /** @test */
    public function it_does_not_search_in_name_if_not_allowed()
    {
        $model1 = TestModel::create(['name' => 'abc']);
        $model2 = TestModel::create(['name' => 'def']);

        $models = $this->createQueryFromSearchRequest('abc')
            ->get();

        $this->assertCount(TestModel::count(), $models);
    }

    protected function createQueryFromSearchRequest(string $search): QueryBuilder
    {
        $request = new Request([
            'q' => $search,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
