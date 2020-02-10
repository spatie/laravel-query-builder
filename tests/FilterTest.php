<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Filters\Filter as CustomFilter;
use Spatie\QueryBuilder\Filters\Filter as FilterInterface;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

class FilterTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 5)->create();
    }

    /** @test */
    public function it_can_filter_models_by_partial_property_by_default()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'name' => $this->models->first()->name,
            ])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_filter_partially_and_case_insensitive()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'name' => strtoupper($this->models->first()->name),
            ])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_filter_results_based_on_the_partial_existence_of_a_property_in_an_array()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'uvwxyz']);

        $results = $this
            ->createQueryFromFilterRequest([
                'name' => 'abc,xyz',
            ])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_filter_models_and_return_an_empty_collection()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'name' => 'None existing first name',
            ])
            ->allowedFilters('name')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_can_filter_a_custom_base_query_with_select()
    {
        $request = new Request([
            'filter' => ['name' => 'john'],
        ]);

        $queryBuilderSql = QueryBuilder::for(TestModel::select('id', 'name'), $request)
            ->allowedFilters('name', 'id')
            ->toSql();

        $expectedSql = TestModel::select('id', 'name')
            ->where(DB::raw('LOWER(`name`)'), 'LIKE', 'john')
            ->toSql();

        $this->assertEquals($expectedSql, $queryBuilderSql);
    }

    /** @test */
    public function it_can_filter_results_based_on_the_existence_of_a_property_in_an_array()
    {
        $results = $this
            ->createQueryFromFilterRequest([
                'id' => '1,2',
            ])
            ->allowedFilters(AllowedFilter::exact('id'))
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([1, 2], $results->pluck('id')->all());
    }

    /** @test */
    public function it_ignores_empty_values_in_an_array_partial_filter()
    {
        $results = $this
            ->createQueryFromFilterRequest([
                'id' => '2,',
            ])
            ->allowedFilters(AllowedFilter::partial('id'))
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals([2], $results->pluck('id')->all());
    }

    /** @test */
    public function it_ignores_an_empty_array_partial_filter()
    {
        $results = $this
            ->createQueryFromFilterRequest([
                'id' => ',,',
            ])
            ->allowedFilters(AllowedFilter::partial('id'))
            ->get();

        $this->assertCount(5, $results);
    }

    /** @test */
    public function it_can_filter_and_match_results_by_exact_property()
    {
        $testModel = TestModel::first();

        $models = TestModel::where('id', $testModel->id)
            ->get();

        $modelsResult = $this
            ->createQueryFromFilterRequest([
                'id' => $testModel->id,
            ])
            ->allowedFilters(AllowedFilter::exact('id'))
            ->get();

        $this->assertEquals($modelsResult, $models);
    }

    /** @test */
    public function it_can_filter_and_reject_results_by_exact_property()
    {
        $testModel = TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest([
                'name' => ' Testing ',
            ])
            ->allowedFilters(AllowedFilter::exact('name'))
            ->get();

        $this->assertCount(0, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_scope()
    {
        $testModel = TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest(['named' => 'John Testing Doe'])
            ->allowedFilters(AllowedFilter::scope('named'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_type_hinted_scope()
    {
        TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest(['user' => 1])
            ->allowedFilters(AllowedFilter::scope('user'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_regular_and_type_hinted_scope()
    {
        TestModel::create(['id'=> 1000, 'name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest(['user_info' => ['id' => '1000', 'name' => 'John Testing Doe']])
            ->allowedFilters(AllowedFilter::scope('user_info'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_scope_with_multiple_parameters()
    {
        Carbon::setTestNow(Carbon::parse('2016-05-05'));

        $testModel = TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest(['created_between' => '2016-01-01,2017-01-01'])
            ->allowedFilters(AllowedFilter::scope('created_between'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_scope_with_multiple_parameters_in_an_associative_array()
    {
        Carbon::setTestNow(Carbon::parse('2016-05-05'));

        $testModel = TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest(['created_between' => ['start' => '2016-01-01', 'end' => '2017-01-01']])
            ->allowedFilters(AllowedFilter::scope('created_between'))
            ->get();

        $this->assertCount(1, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_a_custom_filter_class()
    {
        $testModel = $this->models->first();

        $filterClass = new class implements FilterInterface {
            public function __invoke(Builder $query, $value, string $property): Builder
            {
                return $query->where('name', $value);
            }
        };

        $modelResult = $this
            ->createQueryFromFilterRequest([
                'custom_name' => $testModel->name,
            ])
            ->allowedFilters(AllowedFilter::custom('custom_name', $filterClass))
            ->first();

        $this->assertEquals($testModel->id, $modelResult->id);
    }

    /** @test */
    public function it_can_allow_multiple_filters()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([
                'name' => 'abc',
            ])
            ->allowedFilters('name', AllowedFilter::exact('id'))
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_allow_multiple_filters_as_an_array()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([
                'name' => 'abc',
            ])
            ->allowedFilters(['name', AllowedFilter::exact('id')])
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_filter_by_multiple_filters()
    {
        $model1 = TestModel::create(['name' => 'abcdef']);
        $model2 = TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([
                'name' => 'abc',
                'id' => "1,{$model1->id}",
            ])
            ->allowedFilters('name', AllowedFilter::exact('id'))
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals([$model1->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_guards_against_invalid_filters()
    {
        $this->expectException(InvalidFilterQuery::class);

        $this
            ->createQueryFromFilterRequest(['name' => 'John'])
            ->allowedFilters('id');
    }

    /** @test */
    public function it_can_create_a_custom_filter_with_an_instantiated_filter()
    {
        $customFilter = new class('test1') implements CustomFilter {
            /** @var string */
            protected $filter;

            public function __construct(string $filter)
            {
                $this->filter = $filter;
            }

            public function __invoke(Builder $query, $value, string $property): Builder
            {
                return $query;
            }
        };

        TestModel::create(['name' => 'abcdef']);

        $results = $this
            ->createQueryFromFilterRequest([
                '*' => '*',
            ])
            ->allowedFilters('name', AllowedFilter::custom('*', $customFilter))
            ->get();

        $this->assertNotEmpty($results);
    }

    /** @test */
    public function an_invalid_filter_query_exception_contains_the_unknown_and_allowed_filters()
    {
        $exception = new InvalidFilterQuery(collect(['unknown filter']), collect(['allowed filter']));

        $this->assertEquals(['unknown filter'], $exception->unknownFilters->all());
        $this->assertEquals(['allowed filter'], $exception->allowedFilters->all());
    }

    /** @test */
    public function it_allows_for_adding_ignorable_values()
    {
        $shouldBeIgnored = ['', '-1', null, 'ignored_string', 'another_ignored_string'];

        $filter = AllowedFilter::exact('name')->ignore($shouldBeIgnored[0]);
        $filter
            ->ignore($shouldBeIgnored[1], $shouldBeIgnored[2])
            ->ignore([$shouldBeIgnored[3], $shouldBeIgnored[4]]);

        $valuesIgnoredByFilter = $filter->getIgnored();

        $this->assertEquals(sort($shouldBeIgnored), sort($valuesIgnoredByFilter));
    }

    /** @test */
    public function it_should_not_apply_a_filter_if_the_supplied_value_is_ignored()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'name' => '-1',
            ])
            ->allowedFilters(AllowedFilter::exact('name')->ignore('-1'))
            ->get();

        $this->assertCount(TestModel::count(), $models);
    }

    /** @test */
    public function it_should_apply_the_filter_on_the_subset_of_allowed_values()
    {
        TestModel::create(['name' => 'John Doe']);
        TestModel::create(['name' => 'John Deer']);

        $models = $this
            ->createQueryFromFilterRequest([
                'name' => 'John Deer,John Doe',
            ])
            ->allowedFilters(AllowedFilter::exact('name')->ignore('John Deer'))
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_take_an_argument_for_custom_column_name_resolution()
    {
        $filter = AllowedFilter::custom('property_name', new FiltersExact, 'property_column_name');

        $this->assertInstanceOf(AllowedFilter::class, $filter);
        $this->assertClassHasAttribute('internalName', get_class($filter));
    }

    /** @test */
    public function it_sets_property_column_name_to_property_name_by_default()
    {
        $filter = AllowedFilter::custom('property_name', new FiltersExact);

        $this->assertEquals($filter->getName(), $filter->getInternalName());
    }

    /** @test */
    public function it_resolves_queries_using_property_column_name()
    {
        $filter = AllowedFilter::custom('nickname', new FiltersExact, 'name');

        TestModel::create(['name' => 'abcdef']);

        $models = $this
            ->createQueryFromFilterRequest([
                'nickname' => 'abcdef',
            ])
            ->allowedFilters($filter)
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_filter_using_boolean_flags()
    {
        TestModel::query()->update(['is_visible' => true]);
        $filter = AllowedFilter::exact('is_visible');

        $models = $this
            ->createQueryFromFilterRequest(['is_visible' => 'false'])
            ->allowedFilters($filter)
            ->get();

        $this->assertCount(0, $models);
        $this->assertGreaterThan(0, TestModel::all()->count());
    }

    /** @test */
    public function it_should_apply_a_default_filter_value_if_nothing_in_request()
    {
        TestModel::create(['name' => 'UniqueJohn Doe']);
        TestModel::create(['name' => 'UniqueJohn Deer']);

        $models = $this
            ->createQueryFromFilterRequest([])
            ->allowedFilters(AllowedFilter::partial('name')->default('UniqueJohn'))
            ->get();

        $this->assertEquals(2, $models->count());
    }

    /** @test */
    public function it_does_not_apply_default_filter_when_filter_exists_and_default_is_set()
    {
        TestModel::create(['name' => 'UniqueJohn UniqueDoe']);
        TestModel::create(['name' => 'UniqueJohn Deer']);

        $models = $this
            ->createQueryFromFilterRequest([
                'name' => 'UniqueDoe',
            ])
            ->allowedFilters(AllowedFilter::partial('name')->default('UniqueJohn'))
            ->get();

        $this->assertEquals(1, $models->count());
    }

    protected function createQueryFromFilterRequest(array $filters): QueryBuilder
    {
        $request = new Request([
            'filter' => $filters,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
