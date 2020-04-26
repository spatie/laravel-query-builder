<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Enums\SortDirection;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Sorts\Sort as SortInterface;
use Spatie\QueryBuilder\Sorts\SortsField;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

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
    public function it_can_sort_a_query_ascending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name')
            ->allowedSorts('name')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_query_descending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('-name')
            ->allowedSorts('name')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` desc');
        $this->assertSortedDescending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_query_by_alias()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name-alias')
            ->allowedSorts([AllowedSort::field('name-alias', 'name')])
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_wont_sort_by_columns_that_werent_allowed_first()
    {
        $this->createQueryFromSortRequest('name')->get();

        $this->assertQueryLogDoesntContain('order by `name`');
    }

    /** @test */
    public function it_can_allow_a_descending_sort_by_still_sort_ascending()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('name')
            ->allowedSorts('-name')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_query_by_a_related_property()
    {
        $request = new Request([
            'sort' => 'related_models.name',
            'includes' => 'relatedModel',
        ]);

        $sortedQuery = QueryBuilder::for(TestModel::class, $request)
            ->allowedIncludes('relatedModels')
            ->allowedSorts('related_models.name')
            ->toSql();

        $this->assertEquals('select * from `test_models` order by `related_models`.`name` asc', $sortedQuery);
    }

    /** @test */
    public function it_can_sort_by_json_property_if_its_an_allowed_sort()
    {
        TestModel::query()->update(['name' => json_encode(['first' => 'abc'])]);

        $this
            ->createQueryFromSortRequest('-name->first')
            ->allowedSorts(['name->first'])
            ->get();

        $expectedQuery = TestModel::query()->orderByDesc('name->first')->toSql();

        $this->assertQueryExecuted($expectedQuery);
    }

    /** @test */
    public function it_can_sort_by_sketchy_alias_if_its_an_allowed_sort()
    {
        $sortedModels = $this
            ->createQueryFromSortRequest('-sketchy<>sort')
            ->allowedSorts(AllowedSort::field('sketchy<>sort', 'name'))
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` desc');
        $this->assertSortedDescending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_sort_a_query_with_custom_select()
    {
        $request = new Request([
            'sort' => '-id',
        ]);

        QueryBuilder::for(TestModel::select('id', 'name'), $request)
            ->allowedSorts('id')
            ->defaultSort('id')
            ->paginate(15);

        $this->assertQueryExecuted('select `id`, `name` from `test_models` order by `id` desc limit 15 offset 0');
    }

    /** @test */
    public function it_can_sort_a_chunk_query()
    {
        $this
            ->createQueryFromSortRequest('-name')
            ->allowedSorts('name')
            ->chunk(100, function ($models) {
                //
            });

        $this->assertQueryExecuted('select * from `test_models` order by `name` desc limit 100 offset 0');
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
        $exception = InvalidSortQuery::sortsNotAllowed(collect(['unknown sort']), collect(['allowed sort']));

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
    public function it_wont_sort_sketchy_sort_requests()
    {
        $this
            ->createQueryFromSortRequest('id->"\') asc --injection')
            ->get();

        $this->assertQueryLogDoesntContain('--injection');
    }

    /** @test */
    public function it_uses_default_sort_parameter_when_no_sort_was_requested()
    {
        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->defaultSort('name')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_doesnt_use_the_default_sort_parameter_when_a_sort_was_requested()
    {
        $this->createQueryFromSortRequest('id')
            ->allowedSorts('id')
            ->defaultSort('name')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `id` asc');
    }

    /** @test */
    public function it_allows_default_custom_sort_class_parameter()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, bool $descending, string $property): Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts(AllowedSort::custom('custom_name', $sortClass))
            ->defaultSort(AllowedSort::custom('custom_name', $sortClass))
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_uses_default_descending_sort_parameter()
    {
        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts('-name')
            ->defaultSort('-name')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` desc');
        $this->assertSortedDescending($sortedModels, 'name');
    }

    /** @test */
    public function it_allows_multiple_default_sort_parameters()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, $descending, string $property): Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts(AllowedSort::custom('custom_name', $sortClass), 'id')
            ->defaultSort(AllowedSort::custom('custom_name', $sortClass), '-id')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc, `id` desc');
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

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
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
        $this->assertQueryExecuted('select * from `test_models` order by `name` asc, `id` desc');
        $this->assertEquals($expected->pluck('id'), $sortedModels->pluck('id'));
    }

    /** @test */
    public function it_can_sort_by_a_custom_sort_class()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, $descending, string $property): Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = $this
            ->createQueryFromSortRequest('custom_name')
            ->allowedSorts(AllowedSort::custom('custom_name', $sortClass))
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` asc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_can_take_an_argument_for_custom_column_name_resolution()
    {
        $sort = AllowedSort::custom('property_name', new SortsField, 'property_column_name');

        $this->assertInstanceOf(AllowedSort::class, $sort);
        $this->assertClassHasAttribute('internalName', get_class($sort));
    }

    /** @test */
    public function it_sets_property_column_name_to_property_name_by_default()
    {
        $sort = AllowedSort::custom('property_name', new SortsField);

        $this->assertEquals($sort->getName(), $sort->getInternalName());
    }

    /** @test */
    public function it_resolves_queries_using_property_column_name()
    {
        $sort = AllowedSort::custom('nickname', new SortsField, 'name');

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
            ->allowedSorts(AllowedSort::field('exposed_property_name', 'name'))
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` desc');
    }

    /** @test */
    public function it_does_not_add_sort_clauses_multiple_times()
    {
        $sql = QueryBuilder::for(TestModel::class)
            ->defaultSort('name')
            ->toSql();

        $this->assertSame('select * from `test_models` order by `name` asc', $sql);
    }

    /** @test */
    public function given_a_default_sort_a_sort_alias_will_still_be_resolved()
    {
        $sql = $this->createQueryFromSortRequest('-joined')
            ->defaultSort('name')
            ->allowedSorts(AllowedSort::field('joined', 'created_at'))
            ->toSql();

        $this->assertSame('select * from `test_models` order by `created_at` desc', $sql);
    }

    /** @test */
    public function late_specified_sorts_still_check_for_allowance()
    {
        $query = $this->createQueryFromSortRequest('created_at');

        $this->assertSame('select * from `test_models`', $query->toSql());

        $this->expectException(InvalidSortQuery::class);

        $query->allowedSorts(AllowedSort::field('name-alias', 'name'));
    }

    /** @test */
    public function it_can_sort_and_use_scoped_filters_at_the_same_time()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, $descending, string $property): Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = QueryBuilder::for(TestModel::class, new Request([
            'filter' => [
                'name' => 'foo',
                'between' => '2016-01-01,2017-01-01',
            ],
            'sort' => '-custom',
        ]))
            ->allowedFilters([
                AllowedFilter::scope('name', 'named'),
                AllowedFilter::scope('between', 'createdBetween'),
            ])
            ->allowedSorts([
                AllowedSort::custom('custom', $sortClass),
            ])
            ->defaultSort('foo')
            ->get();

        $this->assertQueryExecuted('select * from `test_models` where `name` = ? and `created_at` between ? and ? order by `name` desc');
        $this->assertSortedAscending($sortedModels, 'name');
    }

    /** @test */
    public function it_ignores_non_existing_sorts_before_adding_them_as_an_alias()
    {
        $query = $this->createQueryFromSortRequest('-alias');

        $this->assertSame('select * from `test_models`', $query->toSql());

        $query->allowedSorts(AllowedSort::field('alias', 'name'));

        $this->assertSame('select * from `test_models` order by `name` desc', $query->toSql());
    }

    /** @test */
    public function raw_sorts_do_not_get_purged_when_specifying_allowed_sorts()
    {
        $query = $this->createQueryFromSortRequest('-name')
            ->orderByRaw('RANDOM()')
            ->allowedSorts('name');

        $this->assertSame('select * from `test_models` order by RANDOM(), `name` desc', $query->toSql());
    }

    /** @test */
    public function the_default_direction_of_an_allow_sort_can_be_set()
    {
        $sortClass = new class implements SortInterface {
            public function __invoke(Builder $query, bool $descending, string $property): Builder
            {
                return $query->orderBy('name', $descending ? 'desc' : 'asc');
            }
        };

        $sortedModels = QueryBuilder::for(TestModel::class, new Request())
            ->allowedSorts(AllowedSort::custom('custom_name', $sortClass))
            ->defaultSort(AllowedSort::custom('custom_name', $sortClass)->defaultDirection(SortDirection::DESCENDING))
            ->get();

        $this->assertQueryExecuted('select * from `test_models` order by `name` desc');
        $this->assertSortedDescending($sortedModels, 'name');
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
