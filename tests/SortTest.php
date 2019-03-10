<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\Sort;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\SortsField;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\Sorts\Sort as SortInterface;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;

class SortTest extends TestCase
{
    use AssertsCollectionSorting;

    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        DB::enableQueryLog();

        $this->models = factory(TestModel::class, 5)->create();
    }

    /** @test */
    public function it_can_sort_a_collection_ascending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_collection_descending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('-name')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" desc');
        $this->assertSortedDescending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_query_with_custom_select()
    {
        $request = new Request([
            'sort' => '-id',
        ]);

        QueryBuilder::for(TestModel::select('id', 'name'), $request)
            ->allowedSorts('-id', 'id')
            ->defaultSort('id')
            ->paginate(15);

        $this->assertQueryExecuted('select "id", "name" from "test_models" order by "id" desc limit 15 offset 0');
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
        $exception = new InvalidSortQuery(collect(['unknown sort']), collect(['allowed sort']));

        $this->assertEquals(['unknown sort'], $exception->unknownSorts->all());
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

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_allows_default_custom_sort_class_parameter()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, $descending, string $property) : Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts(Sort::custom('custom_name', get_class($sortClass)))
            ->defaultSort(Sort::custom('custom_name', get_class($sortClass)))
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_uses_default_descending_sort_parameter()
    {
        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts('-name')
            ->defaultSort('-name')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" desc');
        $this->assertSortedDescending($sortedModels, 'name');
    }

    /** @test */
    public function it_allows_multiple_default_sort_parameters()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, $descending, string $property) : Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts(Sort::custom('custom_name', get_class($sortClass)), 'id')
            ->defaultSort(Sort::custom('custom_name', get_class($sortClass)), '-id')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc, "id" desc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_allow_multiple_sort_parameters()
    {
        DB::enableQueryLog();
        $sortedModels = $this
            ->createQueryFromSortRequest('name')
            ->allowedSorts('id', 'name')
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_allow_multiple_sort_parameters_as_an_array()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name')
            ->allowedSorts(['id', 'name'])
            ->get();

        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_by_multiple_columns()
    {
        factory(TestModel::class, 3)->create(['name' => 'foo']);

        $sortedModels = $this
            ->createQueryFromSortRequest('name,-id')
            ->allowedSorts('name', 'id')
            ->get();

        $expected = TestModel::orderBy('name')->orderByDesc('id');
        $this->assertQueryExecuted('select * from "test_models" order by "name" asc, "id" desc');
        $this->assertEquals($expected->pluck('id'), $sortedModels->pluck('id'));
    }

    /** @test */
    public function it_can_sort_by_a_custom_sort_class()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, $descending, string $property) : Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = $this
            ->createQueryFromSortRequest('custom_name')
            ->allowedSorts(Sort::custom('custom_name', get_class($sortClass)))
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_take_an_argument_for_custom_column_name_resolution()
    {
        $sort = Sort::custom('property_name', SortsField::class, 'property_column_name');

        $this->assertInstanceOf(Sort::class, $sort);
        $this->assertClassHasAttribute('columnName', get_class($sort));
    }

    /** @test */
    public function it_sets_property_column_name_to_property_name_by_default()
    {
        $sort = Sort::custom('property_name', SortsField::class);

        $this->assertEquals($sort->getProperty(), $sort->getColumnName());
    }

    /** @test */
    public function it_resolves_queries_using_property_column_name()
    {
        $sort = Sort::custom('nickname', SortsField::class, 'name');

        $testModel = TestModel::create(['name' => 'zzzzzzzz']);

        $models = $this
            ->createQueryFromSortRequest('nickname')
            ->allowedSorts($sort)
            ->get();

        $this->assertSorted($models, 'name');
        $this->assertTrue($testModel->is($models->last()));
    }

    /** @test */
    public function it_can_sort_descending_with_an_alias()
    {
        $this->createQueryFromSortRequest('-exposed_property_name')
            ->allowedSorts(Sort::field('exposed_property_name', 'name'))
            ->get();

        $this->assertQueryExecuted('select * from "test_models" order by "name" desc');
    }

    /** @test */
    public function it_does_not_add_sort_clauses_multiple_times()
    {
        $sql = QueryBuilder::for(TestModel::class)
            ->defaultSort('name')
            ->toSql();

        $this->assertSame('select * from "test_models" order by "name" asc', $sql);
    }

    /** @test */
    public function given_a_default_sort_a_sort_alias_will_still_be_resolved()
    {
        $sql = $this->createQueryFromSortRequest('-joined')
            ->defaultSort('name')
            ->allowedSorts(Sort::field('joined', 'created_at'))
            ->toSql();

        $this->assertSame('select * from "test_models" order by "created_at" desc', $sql);
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
