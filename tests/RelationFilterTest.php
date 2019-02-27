<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\TestModel;

class RelationFilterTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 5)->create();

        $this->models->each(function (TestModel $model, $index) {
            $model
                ->relatedModels()->create(['name' => $model->name])
                ->nestedRelatedModels()->create(['name' => 'test'.$index]);
        });
    }

    /** @test */
    public function it_can_filter_related_model_property()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'related-models.name' => $this->models->first()->name,
            ])
            ->allowedFilters('related-models.name')
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_filter_results_based_on_the_partial_existence_of_a_property_in_an_array()
    {
        $results = $this
            ->createQueryFromFilterRequest([
                'related-models.nested-related-models.name' => 'est0,est1',
            ])
            ->allowedFilters('related-models.nested-related-models.name')
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([$this->models->get(0)->id, $this->models->get(1)->id], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_filter_models_and_return_an_empty_collection()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'related-models.name' => 'None existing first name',
            ])
            ->allowedFilters('related-models.name')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_can_filter_related_nested_model_property()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'related-models.nested-related-models.name' => 'test',
            ])
            ->allowedFilters('related-models.nested-related-models.name')
            ->get();

        $this->assertCount(5, $models);
    }

    /** @test */
    public function it_can_filter_related_model_and_related_nested_model_property()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'related-models.name' => $this->models->first()->name,
                'related-models.nested-related-models.name' => 'test',
            ])
            ->allowedFilters('related-models.name', 'related-models.nested-related-models.name')
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_can_filter_results_based_on_the_existence_of_a_property_in_an_array()
    {
        $testModel = TestModel::whereIn('id', [1, 2])->get();

        $results = $this
            ->createQueryFromFilterRequest([
                'related-models.id' => $testModel->map(function ($model) {
                    return $model->relatedModels->pluck('id');
                })->flatten()->all(),
            ])
            ->allowedFilters(Filter::exact('related-models.id'))
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals([1, 2], $results->pluck('id')->all());
    }

    /** @test */
    public function it_can_filter_and_reject_results_by_exact_property()
    {
        $testModel = TestModel::create(['name' => 'John Testing Doe']);

        $modelsResult = $this
            ->createQueryFromFilterRequest([
                'related-models.nested-related-models.name' => ' test ',
            ])
            ->allowedFilters(Filter::exact('related-models.nested-related-models.name'))
            ->get();

        $this->assertCount(0, $modelsResult);
    }

    /** @test */
    public function given_the_models_table_name_it_does_filter_by_property_rather_than_relation()
    {
        TestModel::create(['name' => $name = Str::random()]);

        $result = $this
            ->createQueryFromFilterRequest(['test_models.name' => $name])
            ->allowedFilters('test_models.name')
            ->get();

        $this->assertCount(1, $result);
    }

    protected function createQueryFromFilterRequest(array $filters): QueryBuilder
    {
        $request = new Request([
            'filter' => $filters,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
