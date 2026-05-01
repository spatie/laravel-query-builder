<?php

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersGroup;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

it('FiltersGroup is a Filter', function () {
    $group = new FiltersGroup('or', [
        AllowedFilter::partial('name'),
    ]);

    expect($group)->toBeInstanceOf(Filter::class);
});

it('rejects an unknown conjunction', function () {
    expect(fn () => new FiltersGroup('xor', [AllowedFilter::partial('name')]))
        ->toThrow(InvalidArgumentException::class, "must be 'and' or 'or'");
});

it('rejects an empty members array', function () {
    expect(fn () => new FiltersGroup('or', []))
        ->toThrow(InvalidArgumentException::class, 'requires at least one member');
});

it('rejects non-AllowedFilter members', function () {
    expect(fn () => new FiltersGroup('or', ['name', 'full_name']))
        ->toThrow(InvalidArgumentException::class, 'must be AllowedFilter instances');
});

it('groupOr broadcasts shorthand value across members with OR semantics', function () {
    $matchByName = TestModel::factory()->create(['name' => 'BIRTAN special', 'full_name' => 'unrelated A']);
    $matchByFullName = TestModel::factory()->create(['name' => 'unrelated B', 'full_name' => 'BIRTAN full']);
    $noMatch = TestModel::factory()->create(['name' => 'unrelated C', 'full_name' => 'unrelated D']);

    $group = new FiltersGroup('or', [
        AllowedFilter::partial('name'),
        AllowedFilter::partial('full_name'),
    ]);

    $query = TestModel::query();
    $group($query, 'BIRTAN', 'q');

    $ids = $query->get()->pluck('id')->all();

    expect($ids)
        ->toContain($matchByName->id)
        ->toContain($matchByFullName->id)
        ->not->toContain($noMatch->id);
});

it('groupOr returns matches even when only one member condition is satisfied', function () {
    $onlyName = TestModel::factory()->create(['name' => 'BIRTAN solo', 'full_name' => 'no match']);
    $unrelated = TestModel::factory()->create(['name' => 'no', 'full_name' => 'no']);

    $group = new FiltersGroup('or', [
        AllowedFilter::partial('name'),
        AllowedFilter::partial('full_name'),
    ]);

    $query = TestModel::query();
    $group($query, 'BIRTAN', 'q');

    $ids = $query->get()->pluck('id')->all();

    expect($ids)->toContain($onlyName->id)->not->toContain($unrelated->id);
});

it('groupAnd applies all member conditions with AND semantics', function () {
    $bothMatch = TestModel::factory()->create(['name' => 'sharedTOKEN here', 'full_name' => 'sharedTOKEN there']);
    $onlyName = TestModel::factory()->create(['name' => 'sharedTOKEN solo', 'full_name' => 'unrelated']);
    $onlyFullName = TestModel::factory()->create(['name' => 'unrelated', 'full_name' => 'sharedTOKEN solo']);

    $group = new FiltersGroup('and', [
        AllowedFilter::partial('name'),
        AllowedFilter::partial('full_name'),
    ]);

    $query = TestModel::query();
    $group($query, 'sharedTOKEN', 'g');

    $ids = $query->get()->pluck('id')->all();

    expect($ids)
        ->toContain($bothMatch->id)
        ->not->toContain($onlyName->id)
        ->not->toContain($onlyFullName->id);
});
