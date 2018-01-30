<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Filters\Filter as FilterInterface;

class FilterTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp()
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
    public function it_can_filter_results_based_on_the_existence_of_a_property_in_an_array()
    {
        $results = $this
            ->createQueryFromFilterRequest([
                'id' => '1,2',
            ])
            ->allowedFilters(Filter::exact('id'))
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([1, 2], $results->pluck('id')->all());
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
            ->allowedFilters(Filter::exact('id'))
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
            ->allowedFilters(Filter::exact('name'))
            ->get();

        $this->assertCount(0, $modelsResult);
    }

    /** @test */
    public function it_can_filter_results_by_a_custom_filter_class()
    {
        $testModel = $this->models->first();

        $filterClass = new class implements FilterInterface {
            public function __invoke(Builder $query, $value, string $property) : Builder
            {
                return $query->where('name', $value);
            }
        };

        $modelResult = $this
            ->createQueryFromFilterRequest([
                'custom_name' => $testModel->name,
            ])
            ->allowedFilters(Filter::custom('custom_name', get_class($filterClass)))
            ->first();

        $this->assertEquals($testModel->id, $modelResult->id);
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
    public function an_invalid_filter_query_exception_contains_the_unknown_and_allowed_filters()
    {
        $exception = new InvalidFilterQuery(collect(['unknown filter']), collect(['allowed filter']));

        $this->assertEquals(['unknown filter'], $exception->getUnknownFilters()->all());
        $this->assertEquals(['allowed filter'], $exception->getAllowedFilters()->all());
    }

    protected function createQueryFromFilterRequest(array $filters): QueryBuilder
    {
        $request = new Request([
            'filter' => $filters,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
