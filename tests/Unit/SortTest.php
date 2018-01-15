<?php

namespace Spatie\QueryBuilder\Tests\Unit;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Tests\TestCase;

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
        $this->expectException(InvalidQuery::class);

        $this
            ->createQueryFromSortRequest('name')
            ->allowedSorts('id');
    }

    protected function createQueryFromSortRequest(string $sort): QueryBuilder
    {
        $request = new Request([
            'sort' => $sort,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
