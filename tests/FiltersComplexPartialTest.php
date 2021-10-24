<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

class FiltersComplexPartialTest extends TestCase
{
    /** @var Collection */
    protected $models;

    protected $names = [
        'abcdef',
        'abcxyz',
        'uvwxyz',
        'abcuvw',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->models = Collection::empty();

        foreach ($this->names as $name) {
            $this->models->add(TestModel::create(['name' => $name]));
        }
    }

    private function assertModels(array $names, Collection $results): void
    {
        $this->assertEquals(
            $this->models
                ->filter(static function (TestModel $model) use ($names) {
                    return in_array($model->name, $names, true);
                })
                ->pluck('id')
                ->all(),
            $results->pluck('id')->all()
        );
    }

    protected function createQueryFromFilterRequest(array $filters): QueryBuilder
    {
        $request = new Request([
            'filter' => $filters,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }

    protected function getResults(string $searchString): Collection
    {
        return $this
            ->createQueryFromFilterRequest([
                'name' => $searchString,
            ])
            ->allowedFilters([
                AllowedFilter::complexPartial('name'),
            ])
            ->get();
    }

    /** @test */
    public function it_can_filter_results_based_on_the_negated_partial_existence_of_a_property()
    {
        $results = $this->getResults('-abc');

        $this->assertCount(1, $results);
        $this->assertModels(['uvwxyz'], $results);
    }

    /** @test */
    public function it_can_filter_results_based_on_negated_partial_existence_of_a_property()
    {
        $results = $this->getResults('-abc, -xyz');

        $this->assertCount(3, $results);
        $this->assertModels(['abcdef', 'uvwxyz', 'abcuvw'], $results);

        $results2 = $this->getResults('-abc, -xxx');

        $this->assertCount(4, $results2);
    }

    /** @test */
    public function it_can_filter_results_based_on_the_conjunction_of_partial_existence_of_a_property()
    {
        $results = $this->getResults('abc + def');

        $this->assertCount(1, $results);
        $this->assertModels(['abcdef'], $results);
    }

    /** @test */
    public function it_can_filter_results_based_on_combination_of_conjunction_and_negation_of_partial_existence_of_a_property()
    {
        $results = $this->getResults('abc + -def');

        $this->assertCount(2, $results);
        $this->assertModels(['abcxyz', 'abcuvw'], $results);
    }

    /** @test */
    public function it_can_filter_results_based_on_expressions_in_disjunctive_normal_form_of_partial_existence_of_a_property()
    {
        $results = $this->getResults('abc + def, abc + xyz');

        $this->assertCount(2, $results);
        $this->assertModels(['abcdef', 'abcxyz'], $results);
    }
}
