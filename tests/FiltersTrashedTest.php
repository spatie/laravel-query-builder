<?php

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Tests\TestClasses\Models\SoftDeleteModel;

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

    expect($models)->toHaveCount(2);
})->skip();

it('can filter only trashed', function () {
    $models = createQueryFromFilterRequest([
            'trashed' => 'only',
        ], SoftDeleteModel::class)
        ->allowedFilters(AllowedFilter::trashed())
        ->get();

    expect($models)->toHaveCount(1);
});

it('can filter only trashed by scope directly', function () {
    $models = createQueryFromFilterRequest([
            'only_trashed' => true,
        ], SoftDeleteModel::class)
        ->allowedFilters(AllowedFilter::scope('only_trashed'))
        ->get();

    expect($models)->toHaveCount(1);
});

it('can filter with trashed', function () {
    $models = createQueryFromFilterRequest([
            'trashed' => 'with',
        ], SoftDeleteModel::class)
        ->allowedFilters(AllowedFilter::trashed())
        ->get();

    expect($models)->toHaveCount(3);
});
