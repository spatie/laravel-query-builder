<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

uses(AssertsCollectionSorting::class);

beforeEach(function () {
    DB::enableQueryLog();

    $this->models = TestModel::factory()->count(5)->create();
});

it('should sort by closure', function () {
    $sortedModels = createQueryFromSortRequest('callback')
        ->allowedSorts(AllowedSort::callback('callback', function (Builder $query, $descending) {
            $query->orderBy('name', $descending ? 'DESC' : 'ASC');
        }))
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('should sort by array callback', function () {
    $sortedModels = createQueryFromSortRequest('callback')
        ->allowedSorts(AllowedSort::callback('callback', [$this, 'sortCallback']))
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

// Helpers
function sortCallback(Builder $query, $descending)
{
    $query->orderBy('name', $descending ? 'DESC' : 'ASC');
}
