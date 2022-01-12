<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

class SortsCallbackTest extends TestCase
{
    use AssertsCollectionSorting;

    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        DB::enableQueryLog();

        $this->models =TestModel::factory()->count(5)->create();
    }

    /** @test */
    public function it_should_sort_by_closure()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('callback')
            ->allowedSorts(AllowedSort::callback('callback', function (Builder $query, $descending) {
                $query->orderBy('name', $descending ? 'DESC' : 'ASC');
            }))
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_should_sort_by_array_callback()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('callback')
            ->allowedSorts(AllowedSort::callback('callback', [$this, 'sortCallback']))
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    public function sortCallback(Builder $query, $descending)
    {
        $query->orderBy('name', $descending ? 'DESC' : 'ASC');
    }

    protected function createQueryFromSortRequest(string $sort): QueryBuilder
    {
        $request = new Request([
            'sort' => $sort,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }

    protected function assertQueryExecuted(string $query)
    {
        $queries = array_map(function ($queryLogItem) {
            return $queryLogItem['query'];
        }, DB::getQueryLog());

        $this->assertContains($query, $queries);
    }
}
