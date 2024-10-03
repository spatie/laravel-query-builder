<?php

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

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

    expect($models)->toHaveCount(1);
});

it('can filter results based on the partial existence of a property in an array', function () {
    $results = createQueryFromFilterRequest([
            'relatedModels.nestedRelatedModels.name' => 'est0,est1',
        ])
        ->allowedFilters('relatedModels.nestedRelatedModels.name')
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->all())->toEqual([$this->models->get(0)->id, $this->models->get(1)->id]);
});

it('can filter models and return an empty collection', function () {
    $models = createQueryFromFilterRequest([
            'relatedModels.name' => 'None existing first name',
        ])
        ->allowedFilters('relatedModels.name')
        ->get();

    expect($models)->toHaveCount(0);
});

it('can filter related nested model property', function () {
    $models = createQueryFromFilterRequest([
            'relatedModels.nestedRelatedModels.name' => 'test',
        ])
        ->allowedFilters('relatedModels.nestedRelatedModels.name')
        ->get();

    expect($models)->toHaveCount(5);
});

it('can filter related model and related nested model property', function () {
    $models = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
            'relatedModels.nestedRelatedModels.name' => 'test',
        ])
        ->allowedFilters('relatedModels.name', 'relatedModels.nestedRelatedModels.name')
        ->get();

    expect($models)->toHaveCount(1);
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

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->all())->toEqual([1, 2]);
});

it('can filter and reject results by exact property', function () {
    $testModel = TestModel::create(['name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest([
            'relatedModels.nestedRelatedModels.name' => ' test ',
        ])
        ->allowedFilters(AllowedFilter::exact('relatedModels.nestedRelatedModels.name'))
        ->get();

    expect($modelsResult)->toHaveCount(0);
});

it('can disable exact filtering based on related model properties', function () {
    $addRelationConstraint = false;

    $sql = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::exact('relatedModels.name', null, $addRelationConstraint))
        ->toSql();

    expect($sql)->toContain('`relatedModels`.`name` = ');
});

it('can disable partial filtering based on related model properties', function () {
    $addRelationConstraint = false;

    $sql = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::partial('relatedModels.name', null, $addRelationConstraint))
        ->toSql();

    expect($sql)->toContain('LOWER(`relatedModels`.`name`) LIKE ?');
});

it('can disable operator filtering based on related model properties', function () {
    $addRelationConstraint = false;

    $sql = createQueryFromFilterRequest([
            'relatedModels.name' => $this->models->first()->name,
        ])
        ->allowedFilters(AllowedFilter::operator('relatedModels.name', FilterOperator::EQUAL, 'and', null, $addRelationConstraint))
        ->toSql();

    expect($sql)->toContain('`relatedModels`.`name` = ?');
});
