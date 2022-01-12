<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

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

    expect($models)->toHaveCount(1);
});

it('should filter by array callback', function () {
    $models = createQueryFromFilterRequest([
            'callback' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::callback('callback', [$this, 'filterCallback']))
        ->get();

    expect($models)->toHaveCount(1);
});

// Helpers
function filterCallback(Builder $query, $value)
{
    $query->where('name', $value);
}
