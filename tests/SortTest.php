<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\assertObjectHasProperty;

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Enums\SortDirection;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Sorts\Sort as SortInterface;
use Spatie\QueryBuilder\Sorts\SortsField;
use Spatie\QueryBuilder\Tests\Concerns\AssertsCollectionSorting;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

uses(AssertsCollectionSorting::class);

beforeEach(function () {
    DB::enableQueryLog();

    $this->models = TestModel::factory()->count(5)->create();
});

it('can sort a query ascending', function () {
    $sortedModels = createQueryFromSortRequest('name')
        ->allowedSorts('name')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('has the allowed sorts property set even if no sorts are requested', function () {
    $queryBuilder = createQueryFromSortRequest()
        ->allowedSorts('name');

    expect(invade($queryBuilder)->allowedSorts)->not->toBeEmpty();
});

it('can sort a query descending', function () {
    $sortedModels = createQueryFromSortRequest('-name')
        ->allowedSorts('name')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` desc');
    $this->assertSortedDescending($sortedModels, 'name');
});

it('can sort a query by alias', function () {
    $sortedModels = createQueryFromSortRequest('name-alias')
        ->allowedSorts([AllowedSort::field('name-alias', 'name')])
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('wont sort by columns that werent allowed first', function () {
    createQueryFromSortRequest('name')->get();

    $this->assertQueryLogDoesntContain('order by `name`');
});

it('can allow a descending sort by still sort ascending', function () {
    $sortedModels = createQueryFromSortRequest('name')
        ->allowedSorts('-name')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('can sort a query by a related property', function () {
    $request = new Request([
        'sort' => 'related_models.name',
        'includes' => 'relatedModel',
    ]);

    $sortedQuery = QueryBuilder::for(TestModel::class, $request)
        ->allowedIncludes('relatedModels')
        ->allowedSorts('related_models.name')
        ->toSql();

    expect($sortedQuery)->toEqual('select * from `test_models` order by `related_models`.`name` asc');
});

it('can sort by json property if its an allowed sort', function () {
    TestModel::query()->update(['name' => json_encode(['first' => 'abc'])]);

    createQueryFromSortRequest('-name->first')
        ->allowedSorts(['name->first'])
        ->get();

    $expectedQuery = TestModel::query()->orderByDesc('name->first')->toSql();

    assertQueryExecuted($expectedQuery);
});

it('can sort by sketchy alias if its an allowed sort', function () {
    $sortedModels = createQueryFromSortRequest('-sketchy<>sort')
        ->allowedSorts(AllowedSort::field('sketchy<>sort', 'name'))
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` desc');
    $this->assertSortedDescending($sortedModels, 'name');
});

it('can sort a query with custom select', function () {
    $request = new Request([
        'sort' => '-id',
    ]);

    QueryBuilder::for(TestModel::select('id', 'name'), $request)
        ->allowedSorts('id')
        ->defaultSort('id')
        ->paginate(15);

    assertQueryExecuted('select `id`, `name` from `test_models` order by `id` desc limit 15 offset 0');
});

it('can sort a chunk query', function () {
    createQueryFromSortRequest('-name')
        ->allowedSorts('name')
        ->chunk(100, function ($models) {
            //
        });

    assertQueryExecuted('select * from `test_models` order by `name` desc limit 100 offset 0');
});

it('can guard against sorts that are not allowed', function () {
    $sortedModels = createQueryFromSortRequest('name')
        ->allowedSorts('name')
        ->get();

    $this->assertSortedAscending($sortedModels, 'name');
});

it('will throw an exception if a sort property is not allowed', function () {
    $this->expectException(InvalidSortQuery::class);

    createQueryFromSortRequest('name')
        ->allowedSorts('id');
});

it('does not throw invalid sort query exception when disable in config', function () {
    config(['query-builder.disable_invalid_sort_query_exception' => true]);

    createQueryFromSortRequest('name')
        ->allowedSorts('id');

    expect(true)->toBeTrue();
});

test('an invalid sort query exception contains the unknown and allowed sorts', function () {
    $exception = InvalidSortQuery::sortsNotAllowed(collect(['unknown sort']), collect(['allowed sort']));

    expect($exception->unknownSorts->all())->toEqual(['unknown sort']);
    expect($exception->allowedSorts->all())->toEqual(['allowed sort']);
});

it('wont sort if no sort query parameter is given', function () {
    $builderQuery = QueryBuilder::for(TestModel::class, new Request())
        ->allowedSorts('name')
        ->toSql();

    $eloquentQuery = TestModel::query()->toSql();

    expect($builderQuery)->toEqual($eloquentQuery);
});

it('wont sort sketchy sort requests', function () {
    createQueryFromSortRequest('id->"\') asc --injection')
        ->get();

    $this->assertQueryLogDoesntContain('--injection');
});

it('uses default sort parameter when no sort was requested', function () {
    $sortedModels = QueryBuilder::for(TestModel::class, new Request())
        ->defaultSort('name')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('doesnt use the default sort parameter when a sort was requested', function () {
    createQueryFromSortRequest('id')
        ->allowedSorts('id')
        ->defaultSort('name')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `id` asc');
});

it('allows default custom sort class parameter', function () {
    $sortClass = new class () implements SortInterface {
        public function __invoke(Builder $query, bool $descending, string $property): Builder
        {
            return $query->orderBy('name', $descending ? 'desc' : 'asc');
        }
    };

    $sortedModels = QueryBuilder::for(TestModel::class, new Request())
        ->allowedSorts(AllowedSort::custom('custom_name', $sortClass))
        ->defaultSort(AllowedSort::custom('custom_name', $sortClass))
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('uses default descending sort parameter', function () {
    $sortedModels = QueryBuilder::for(TestModel::class, new Request())
        ->allowedSorts('-name')
        ->defaultSort('-name')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` desc');
    $this->assertSortedDescending($sortedModels, 'name');
});

it('allows multiple default sort parameters', function () {
    $sortClass = new class () implements SortInterface {
        public function __invoke(Builder $query, $descending, string $property): Builder
        {
            return $query->orderBy('name', $descending ? 'desc' : 'asc');
        }
    };

    $sortedModels = QueryBuilder::for(TestModel::class, new Request())
        ->allowedSorts(AllowedSort::custom('custom_name', $sortClass), 'id')
        ->defaultSort(AllowedSort::custom('custom_name', $sortClass), '-id')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc, `id` desc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('allows multiple default sort parameters in an array', function () {
    $sortClass = new class () implements SortInterface {
        public function __invoke(Builder $query, $descending, string $property): Builder
        {
            return $query->orderBy('name', $descending ? 'desc' : 'asc');
        }
    };

    $sortedModels = QueryBuilder::for(TestModel::class, new Request())
        ->allowedSorts(AllowedSort::custom('custom_name', $sortClass), 'id')
        ->defaultSort([AllowedSort::custom('custom_name', $sortClass), '-id'])
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc, `id` desc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('can allow multiple sort parameters', function () {
    DB::enableQueryLog();
    $sortedModels = createQueryFromSortRequest('name')
        ->allowedSorts('id', 'name')
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('can allow multiple sort parameters as an array', function () {
    $sortedModels = createQueryFromSortRequest('name')
        ->allowedSorts(['id', 'name'])
        ->get();

    $this->assertSortedAscending($sortedModels, 'name');
});

it('can sort by multiple columns', function () {
    $this->models = TestModel::factory()->count(3)->create(['name' => 'foo']);

    $sortedModels = createQueryFromSortRequest('name,-id')
        ->allowedSorts('name', 'id')
        ->get();

    $expected = TestModel::orderBy('name')->orderByDesc('id');
    assertQueryExecuted('select * from `test_models` order by `name` asc, `id` desc');
    expect($sortedModels->pluck('id'))->toEqual($expected->pluck('id'));
});

it('can sort by a custom sort class', function () {
    $sortClass = new class () implements SortInterface {
        public function __invoke(Builder $query, $descending, string $property): Builder
        {
            return $query->orderBy('name', $descending ? 'desc' : 'asc');
        }
    };

    $sortedModels = createQueryFromSortRequest('custom_name')
        ->allowedSorts(AllowedSort::custom('custom_name', $sortClass))
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` asc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('can take an argument for custom column name resolution', function () {
    $sort = AllowedSort::custom('property_name', new SortsField(), 'property_column_name');

    expect($sort)->toBeInstanceOf(AllowedSort::class);
    assertObjectHasProperty('internalName', $sort);
});

it('sets property column name to property name by default', function () {
    $sort = AllowedSort::custom('property_name', new SortsField());

    expect($sort->getInternalName())->toEqual($sort->getName());
});

it('resolves queries using property column name', function () {
    $sort = AllowedSort::custom('nickname', new SortsField(), 'name');

    $testModel = TestModel::create(['name' => 'zzzzzzzz']);

    $models = createQueryFromSortRequest('nickname')
        ->allowedSorts($sort)
        ->get();

    $this->assertSorted($models, 'name');
    expect($testModel->is($models->last()))->toBeTrue();
});

it('can sort descending with an alias', function () {
    createQueryFromSortRequest('-exposed_property_name')
        ->allowedSorts(AllowedSort::field('exposed_property_name', 'name'))
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` desc');
});

it('does not add sort clauses multiple times', function () {
    $sql = QueryBuilder::for(TestModel::class)
        ->defaultSort('name')
        ->toSql();

    expect($sql)->toBe('select * from `test_models` order by `name` asc');
});

test('given a default sort a sort alias will still be resolved', function () {
    $sql = createQueryFromSortRequest('-joined')
        ->defaultSort('name')
        ->allowedSorts(AllowedSort::field('joined', 'created_at'))
        ->toSql();

    expect($sql)->toBe('select * from `test_models` order by `created_at` desc');
});

test('late specified sorts still check for allowance', function () {
    $query = createQueryFromSortRequest('created_at');

    expect($query->toSql())->toBe('select * from `test_models`');

    $this->expectException(InvalidSortQuery::class);

    $query->allowedSorts(AllowedSort::field('name-alias', 'name'));
});

it('can sort and use scoped filters at the same time', function () {
    $sortClass = new class () implements SortInterface {
        public function __invoke(Builder $query, $descending, string $property): Builder
        {
            return $query->orderBy('name', $descending ? 'desc' : 'asc');
        }
    };

    $sortedModels = QueryBuilder::for(TestModel::class, new Request([
        'filter' => [
            'name' => 'foo',
            'between' => '2016-01-01,2017-01-01',
        ],
        'sort' => '-custom',
    ]))
        ->allowedFilters([
            AllowedFilter::scope('name', 'named'),
            AllowedFilter::scope('between', 'createdBetween'),
        ])
        ->allowedSorts([
            AllowedSort::custom('custom', $sortClass),
        ])
        ->defaultSort('foo')
        ->get();

    assertQueryExecuted('select * from `test_models` where `name` = ? and `created_at` between ? and ? order by `name` desc');
    $this->assertSortedAscending($sortedModels, 'name');
});

it('ignores non existing sorts before adding them as an alias', function () {
    $query = createQueryFromSortRequest('-alias');

    expect($query->toSql())->toBe('select * from `test_models`');

    $query->allowedSorts(AllowedSort::field('alias', 'name'));

    expect($query->toSql())->toBe('select * from `test_models` order by `name` desc');
});

test('raw sorts do not get purged when specifying allowed sorts', function () {
    $query = createQueryFromSortRequest('-name')
        ->orderByRaw('RANDOM()')
        ->allowedSorts('name');

    expect($query->toSql())->toBe('select * from `test_models` order by RANDOM(), `name` desc');
});

test('the default direction of an allow sort can be set', function () {
    $sortClass = new class () implements SortInterface {
        public function __invoke(Builder $query, bool $descending, string $property): Builder
        {
            return $query->orderBy('name', $descending ? 'desc' : 'asc');
        }
    };

    $sortedModels = QueryBuilder::for(TestModel::class, new Request())
        ->allowedSorts(AllowedSort::custom('custom_name', $sortClass))
        ->defaultSort(AllowedSort::custom('custom_name', $sortClass)->defaultDirection(SortDirection::DESCENDING))
        ->get();

    assertQueryExecuted('select * from `test_models` order by `name` desc');
    $this->assertSortedDescending($sortedModels, 'name');
});

// Helpers
function createQueryFromSortRequest(?string $sort = null): QueryBuilder
{
    $request = new Request($sort ? [
        'sort' => $sort,
    ] : []);

    return QueryBuilder::for(TestModel::class, $request);
}
