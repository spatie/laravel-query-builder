<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

uses(TestCase::class);

beforeEach(function () {
    $this->models = TestModel::factory()->count(3)->create();
});

it('should filter by closure', function () {
    $models = createQueryFromFilterRequest([
            'callback' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::callback('callback', function (Builder $query, $value) {
            $query->where('name', $value);
        }))
        ->get();

    $this->assertCount(1, $models);
});

it('should filter by array callback', function () {
    $models = createQueryFromFilterRequest([
            'callback' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::callback('callback', [$this, 'filterCallback']))
        ->get();

    $this->assertCount(1, $models);
});

// Helpers
function filterCallback(Builder $query, $value)
{
    $query->where('name', $value);
}

function createQueryFromFilterRequest(array $filters): QueryBuilder
{
    $request = new Request([
        'filter' => $filters,
    ]);

    return QueryBuilder::for(TestModel::class, $request);
}
