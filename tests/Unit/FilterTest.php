<?php

namespace Spatie\QueryBuilder\Tests\Unit;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Tests\TestCase;

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
    public function it_guards_against_invalid_filters()
    {
        $this->expectException(InvalidQuery::class);

        $this
            ->createQueryFromFilterRequest(['name' => 'John'])
            ->allowedFilters('id');
    }

    /** @test */
    public function it_can_filter_models()
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
    public function it_can_filter_case_insensitive()
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
    public function it_can_filter_results_by_id()
    {
        $testModel = TestModel::first();

        $modelsResult = $this
            ->createQueryFromFilterRequest([
                'id' => $testModel->id,
            ])
            ->allowedFilters('id')
            ->get();

        $models = TestModel::where('id', $testModel->id)
            ->get();

        $this->assertEquals($modelsResult, $models);
    }

    protected function createQueryFromFilterRequest(array $filters): QueryBuilder
    {
        $request = new Request([
            'filter' => $filters,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
