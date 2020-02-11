<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

class FiltersCallbackTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 3)->create();
    }

    /** @test */
    public function it_should_filter_by_closure()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'callback' => $this->models->first()->name,
            ])
            ->allowedFilters(AllowedFilter::callback('callback', function (Builder $query, $value) {
                $query->where('name', $value);
            }))
            ->get();

        $this->assertCount(1, $models);
    }

    /** @test */
    public function it_should_filter_by_array_callback()
    {
        $models = $this
            ->createQueryFromFilterRequest([
                'callback' => $this->models->first()->name,
            ])
            ->allowedFilters(AllowedFilter::callback('callback', [$this, 'filterCallback']))
            ->get();

        $this->assertCount(1, $models);
    }

    public function filterCallback(Builder $query, $value)
    {
        $query->where('name', $value);
    }

    protected function createQueryFromFilterRequest(array $filters): QueryBuilder
    {
        $request = new Request([
            'filter' => $filters,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
