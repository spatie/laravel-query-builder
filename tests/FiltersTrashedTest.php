<?php

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\SoftDeleteModel;

uses(TestCase::class);

beforeEach(function () {
    $this->models = collect([
        SoftDeleteModel::factory()->create(),
        SoftDeleteModel::factory()->create(),

        SoftDeleteModel::factory()->create(['deleted_at' => now()]),
    ]);
});

it('should filter not trashed by default', function () {
    $models = createQueryFromFilterRequest([
            'trashed' => '',
        ])
        ->allowedFilters(AllowedFilter::trashed())
        ->get();

    $this->assertCount(2, $models);
});

it('can filter only trashed', function () {
    $models = createQueryFromFilterRequest([
            'trashed' => 'only',
        ])
        ->allowedFilters(AllowedFilter::trashed())
        ->get();

    $this->assertCount(1, $models);
});

it('can filter only trashed by scope directly', function () {
    $models = createQueryFromFilterRequest([
            'only_trashed' => true,
        ])
        ->allowedFilters(AllowedFilter::scope('only_trashed'))
        ->get();

    $this->assertCount(1, $models);
});

it('can filter with trashed', function () {
    $models = createQueryFromFilterRequest([
            'trashed' => 'with',
        ])
        ->allowedFilters(AllowedFilter::trashed())
        ->get();

    $this->assertCount(3, $models);
});

// Helpers
function createQueryFromFilterRequest(array $filters): QueryBuilder
{
    $request = new Request([
        'filter' => $filters,
    ]);

    return QueryBuilder::for(SoftDeleteModel::class, $request);
}
