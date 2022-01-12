<?php

use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

uses(TestCase::class);

beforeEach(function () {
    $this->models = TestModel::factory()->count(5)->create();

    $this->models->each(function (TestModel $model, $index) {
        $model
            ->relatedModels()->create(['name' => $model->name])
            ->nestedRelatedModels()->create(['name' => 'test'.$index]);
    });
});

it('can filter related model property', function () {
    $models = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
        ])
        ->allowedFilters('relatedModels.name')
        ->get();

    $this->assertCount(1, $models);
});

it('can filter results based on the partial existence of a property in an array', function () {
    $results = createQueryFromFilterRequest([
            'relatedModels.nestedRelatedModels.name' => 'est0,est1',
        ])
        ->allowedFilters('relatedModels.nestedRelatedModels.name')
        ->get();

    $this->assertCount(2, $results);
    $this->assertEquals([$this->models->get(0)->id, $this->models->get(1)->id], $results->pluck('id')->all());
});

it('can filter models and return an empty collection', function () {
    $models = createQueryFromFilterRequest([
            'relatedModels.name' => 'None existing first name',
        ])
        ->allowedFilters('relatedModels.name')
        ->get();

    $this->assertCount(0, $models);
});

it('can filter related nested model property', function () {
    $models = createQueryFromFilterRequest([
            'relatedModels.nestedRelatedModels.name' => 'test',
        ])
        ->allowedFilters('relatedModels.nestedRelatedModels.name')
        ->get();

    $this->assertCount(5, $models);
});

it('can filter related model and related nested model property', function () {
    $models = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
            'relatedModels.nestedRelatedModels.name' => 'test',
        ])
        ->allowedFilters('relatedModels.name', 'relatedModels.nestedRelatedModels.name')
        ->get();

    $this->assertCount(1, $models);
});

it('can filter results based on the existence of a property in an array', function () {
    $testModels = TestModel::whereIn('id', [1, 2])->get();

    $results = createQueryFromFilterRequest([
            'relatedModels.id' => $testModels->map(function ($model) {
                return $model->relatedModels->pluck('id');
            })->flatten()->all(),
        ])
        ->allowedFilters(AllowedFilter::exact('relatedModels.id'))
        ->get();

    $this->assertCount(2, $results);
    $this->assertEquals([1, 2], $results->pluck('id')->all());
});

it('can filter and reject results by exact property', function () {
    $testModel = TestModel::create(['name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest([
            'relatedModels.nestedRelatedModels.name' => ' test ',
        ])
        ->allowedFilters(AllowedFilter::exact('relatedModels.nestedRelatedModels.name'))
        ->get();

    $this->assertCount(0, $modelsResult);
});

it('can disable exact filtering based on related model properties', function () {
    $addRelationConstraint = false;

    $sql = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::exact('relatedModels.name', null, $addRelationConstraint))
        ->toSql();

    $this->assertStringContainsString('`relatedModels`.`name` = ', $sql);
});

it('can disable partial filtering based on related model properties', function () {
    $addRelationConstraint = false;

    $sql = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::partial('relatedModels.name', null, $addRelationConstraint))
        ->toSql();

    $this->assertStringContainsString('LOWER(`relatedModels`.`name`) LIKE ?', $sql);
});

// Helpers
function createQueryFromFilterRequest(array $filters): QueryBuilder
{
    $request = new Request([
        'filter' => $filters,
    ]);

    return QueryBuilder::for(TestModel::class, $request);
}
