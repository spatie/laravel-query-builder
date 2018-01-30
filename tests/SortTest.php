<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;

class SortTest extends TestCase
{
    use AssertsCollectionSorting;

    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp()
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 5)->create();
    }

    /** @test */
    public function it_can_sort_a_collection_ascending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name')
            ->get();

        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_collection_descending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('-name')
            ->get();

        $this->assertSortedDescending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_guard_against_sorts_that_are_not_allowed()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name')
            ->allowedSorts('name')
            ->get();

        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_will_throw_an_exception_if_a_sort_property_is_not_allowed()
    {
        $this->expectException(InvalidSortQuery::class);

        $this
            ->createQueryFromSortRequest('name')
            ->allowedSorts('id');
    }

    /** @test */
    public function an_invalid_sort_query_exception_contains_the_unknown_and_allowed_sorts()
    {
        $exception = new InvalidSortQuery('unknown sort', collect(['allowed sort']));

        $this->assertEquals('unknown sort', $exception->unknownSort);
        $this->assertEquals(['allowed sort'], $exception->allowedSorts->all());
    }

    /** @test */
    public function it_wont_sort_if_no_sort_query_parameter_is_given()
    {
        $builderQuery = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts('name')
            ->toSql();

        $eloquentQuery = TestModel::query()->toSql();

        $this->assertEquals($eloquentQuery, $builderQuery);
    }

    /** @test */
    public function it_uses_default_sort_parameter()
    {
        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts('name')
            ->defaultSort('name')
            ->get();

        $this->assertSortedAscending($sortedModels, 'name');
    }

    protected function createQueryFromSortRequest(string $sort): QueryBuilder
    {
        $request = new Request([
            'sort' => $sort,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
