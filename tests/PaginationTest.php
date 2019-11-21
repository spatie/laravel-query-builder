<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

class PaginationTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->make('config')->set('query-builder.pagination.per-page', [
            'default' => 5,
            'min' => 1,
            'max' => 8,
        ]);

        $this->models = factory(TestModel::class, 10)->create();
    }

    /** @test */
    public function it_can_paginate_query()
    {
        $paginator = $this->createQuery()
            ->pagination();

        $this->assertCount(5, $paginator->items());
    }

    /** @test */
    public function it_respects_per_page()
    {
        $paginator = $this->createQuery(['per-page' => 2])
            ->pagination();

        $this->assertCount(2, $paginator->items());
    }

    /** @test */
    public function it_can_keep_per_page_in_range()
    {
        $paginator = $this->createQuery(['per-page' => 9999])
            ->pagination();

        $this->assertCount(8, $paginator->items());

        $paginator = $this->createQuery(['per-page' => -1])
            ->pagination();

        $this->assertCount(1, $paginator->items());
    }

    /** @test */
    public function it_can_setup_default_per_page_as_scalar()
    {
        $paginator = $this->createQuery()
            ->pagination(3);

        $this->assertCount(3, $paginator->items());
    }

    /** @test */
    public function it_can_setup_per_page_as_options()
    {
        $paginator = $this->createQuery()
            ->pagination([
                'default' => 3,
            ]);

        $this->assertCount(3, $paginator->items());

        $paginator = $this->createQuery(['per-page' => 10])
            ->pagination([
                'max' => 6,
            ]);

        $this->assertCount(6, $paginator->items());
    }

    /** @test */
    public function it_can_simple_paginate_query()
    {
        $paginator = $this->createQuery()
            ->simplePagination();

        $this->assertCount(5, $paginator->items());
    }

    protected function createQuery(array $data = []): QueryBuilder
    {
        $request = new Request($data);

        return QueryBuilder::for(TestModel::class, $request);
    }
}
