<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
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

it('integrates with QueryBuilder via AllowedFilter::groupOr', function () {
    $a = TestModel::factory()->create(['name' => 'integTOKEN-A', 'full_name' => 'irrelevant']);
    $b = TestModel::factory()->create(['name' => 'irrelevant', 'full_name' => 'integTOKEN-B']);
    TestModel::factory()->create(['name' => 'unrelated', 'full_name' => 'unrelated']);

    $request = new Request(['filter' => ['q' => 'integTOKEN']]);

    $results = QueryBuilder::for(TestModel::class, $request)
        ->allowedFilters(
            AllowedFilter::groupOr('q', [
                AllowedFilter::partial('name'),
                AllowedFilter::partial('full_name'),
            ]),
        )
        ->get();

    expect($results->pluck('id')->all())
        ->toContain($a->id)
        ->toContain($b->id)
        ->toHaveCount(2);
});

it('integrates with QueryBuilder via AllowedFilter::groupAnd', function () {
    $shouldMatch = TestModel::factory()->create(['name' => 'andTOKEN A', 'full_name' => 'andTOKEN B']);
    TestModel::factory()->create(['name' => 'andTOKEN solo', 'full_name' => 'no']);
    TestModel::factory()->create(['name' => 'no', 'full_name' => 'andTOKEN solo']);

    $request = new Request(['filter' => ['g' => 'andTOKEN']]);

    $results = QueryBuilder::for(TestModel::class, $request)
        ->allowedFilters(
            AllowedFilter::groupAnd('g', [
                AllowedFilter::partial('name'),
                AllowedFilter::partial('full_name'),
            ]),
        )
        ->get();

    expect($results->pluck('id')->all())->toEqual([$shouldMatch->id]);
});

it('skips the group entirely when the shorthand filter is absent from the URL', function () {
    TestModel::factory()->count(3)->create();

    $request = new Request(['filter' => []]);

    $results = QueryBuilder::for(TestModel::class, $request)
        ->allowedFilters(
            AllowedFilter::groupOr('q', [
                AllowedFilter::partial('name'),
                AllowedFilter::partial('full_name'),
            ]),
        )
        ->get();

    expect($results->count())->toBe(3);
});

it('combines a group filter with a top-level filter using AND', function () {
    $shouldMatch = TestModel::factory()->create(['name' => 'Ali special', 'full_name' => 'birtan@somewhere']);
    TestModel::factory()->create(['name' => 'Ali other', 'full_name' => 'no token']);
    TestModel::factory()->create(['name' => 'Bob', 'full_name' => 'birtan@somewhere']);

    $request = new Request(['filter' => ['name' => 'Ali', 'q' => 'birtan']]);

    $results = QueryBuilder::for(TestModel::class, $request)
        ->allowedFilters(
            AllowedFilter::partial('name'),
            AllowedFilter::groupOr('q', [
                AllowedFilter::partial('name'),
                AllowedFilter::partial('full_name'),
            ]),
        )
        ->get();

    expect($results->pluck('id')->all())->toEqual([$shouldMatch->id]);
});

it('joins multiple independent groups with AND between them', function () {
    $shouldMatch = TestModel::factory()->create(['name' => 'tokenA found', 'full_name' => 'tokenB present']);
    TestModel::factory()->create(['name' => 'tokenA only', 'full_name' => 'no']);
    TestModel::factory()->create(['name' => 'no', 'full_name' => 'tokenB only']);

    $request = new Request([
        'filter' => [
            'g1' => 'tokenA',
            'g2' => 'tokenB',
        ],
    ]);

    $results = QueryBuilder::for(TestModel::class, $request)
        ->allowedFilters(
            AllowedFilter::groupOr('g1', [
                AllowedFilter::partial('name'),
                AllowedFilter::partial('full_name'),
            ]),
            AllowedFilter::groupOr('g2', [
                AllowedFilter::partial('full_name'),
                AllowedFilter::partial('name'),
            ]),
        )
        ->get();

    expect($results->pluck('id')->all())->toEqual([$shouldMatch->id]);
});

it('applies different filter types per member when shorthand is broadcast', function () {
    $partialMatch = TestModel::factory()->create(['name' => 'mixedTOKEN partial substring', 'full_name' => 'no']);
    $exactMatch = TestModel::factory()->create(['name' => 'no', 'full_name' => 'mixedTOKEN']);
    TestModel::factory()->create(['name' => 'no', 'full_name' => 'mixedTOKEN with extra']);

    $request = new Request(['filter' => ['q' => 'mixedTOKEN']]);

    $results = QueryBuilder::for(TestModel::class, $request)
        ->allowedFilters(
            AllowedFilter::groupOr('q', [
                AllowedFilter::partial('name'),
                AllowedFilter::exact('full_name'),
            ]),
        )
        ->get();

    expect($results->pluck('id')->all())
        ->toContain($partialMatch->id)
        ->toContain($exactMatch->id)
        ->toHaveCount(2);
});

it('emits the expected SQL shape for a groupOr combined with a top-level filter', function () {
    DB::enableQueryLog();

    $request = new Request(['filter' => ['name' => 'ali', 'q' => 'birtan']]);

    QueryBuilder::for(TestModel::class, $request)
        ->allowedFilters(
            AllowedFilter::partial('name'),
            AllowedFilter::groupOr('q', [
                AllowedFilter::partial('name'),
                AllowedFilter::partial('full_name'),
            ]),
        )
        ->get();

    assertQueryExecuted(
        'select * from `test_models` where LOWER(`test_models`.`name`) LIKE ? and ((LOWER(`test_models`.`name`) LIKE ?) or (LOWER(`test_models`.`full_name`) LIKE ?))'
    );
});
