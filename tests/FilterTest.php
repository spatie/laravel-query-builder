<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Filters\Filter as CustomFilter;
use Spatie\QueryBuilder\Filters\Filter as FilterInterface;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

uses(TestCase::class);

beforeEach(function () {
    $this->models = TestModel::factory()->count(5)->create();
});

it('can filter models by partial property by default', function () {
    $models = createQueryFromFilterRequest([
            'name' => $this->models->first()->name,
        ])
        ->allowedFilters('name')
        ->get();

    $this->assertCount(1, $models);
});

it('can filter models by an array as filter value', function () {
    $models = createQueryFromFilterRequest([
            'name' => ['first' => $this->models->first()->name],
        ])
        ->allowedFilters('name')
        ->get();

    $this->assertCount(1, $models);
});

it('can filter partially and case insensitive', function () {
    $models = createQueryFromFilterRequest([
            'name' => strtoupper($this->models->first()->name),
        ])
        ->allowedFilters('name')
        ->get();

    $this->assertCount(1, $models);
});

it('can filter results based on the partial existence of a property in an array', function () {
    $model1 = TestModel::create(['name' => 'abcdef']);
    $model2 = TestModel::create(['name' => 'uvwxyz']);

    $results = createQueryFromFilterRequest([
            'name' => 'abc,xyz',
        ])
        ->allowedFilters('name')
        ->get();

    $this->assertCount(2, $results);
    $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
});

it('can filter models and return an empty collection', function () {
    $models = createQueryFromFilterRequest([
            'name' => 'None existing first name',
        ])
        ->allowedFilters('name')
        ->get();

    $this->assertCount(0, $models);
});

it('can filter a custom base query with select', function () {
    $request = new Request([
        'filter' => ['name' => 'john'],
    ]);

    $queryBuilderSql = QueryBuilder::for(TestModel::select('id', 'name'), $request)
        ->allowedFilters('name', 'id')
        ->toSql();

    $expectedSql = TestModel::select('id', 'name')
        ->where(DB::raw('LOWER(`test_models`.`name`)'), 'LIKE', 'john')
        ->toSql();

    $this->assertEquals($expectedSql, $queryBuilderSql);
});

it('can filter results based on the existence of a property in an array', function () {
    $results = createQueryFromFilterRequest([
            'id' => '1,2',
        ])
        ->allowedFilters(AllowedFilter::exact('id'))
        ->get();

    $this->assertCount(2, $results);
    $this->assertEquals([1, 2], $results->pluck('id')->all());
});

it('ignores empty values in an array partial filter', function () {
    $results = createQueryFromFilterRequest([
            'id' => '2,',
        ])
        ->allowedFilters(AllowedFilter::partial('id'))
        ->get();

    $this->assertCount(1, $results);
    $this->assertEquals([2], $results->pluck('id')->all());
});

it('ignores an empty array partial filter', function () {
    $results = createQueryFromFilterRequest([
            'id' => ',,',
        ])
        ->allowedFilters(AllowedFilter::partial('id'))
        ->get();

    $this->assertCount(5, $results);
});

test('falsy values are not ignored when applying a partial filter', function () {
    DB::enableQueryLog();

    createQueryFromFilterRequest([
            'id' => [0],
        ])
        ->allowedFilters(AllowedFilter::partial('id'))
        ->get();

    $this->assertQueryLogContains("select * from `test_models` where (LOWER(`test_models`.`id`) LIKE ?)");
});

it('can filter and match results by exact property', function () {
    $testModel = TestModel::first();

    $models = TestModel::where('id', $testModel->id)
        ->get();

    $modelsResult = createQueryFromFilterRequest([
            'id' => $testModel->id,
        ])
        ->allowedFilters(AllowedFilter::exact('id'))
        ->get();

    $this->assertEquals($modelsResult, $models);
});

it('can filter and reject results by exact property', function () {
    $testModel = TestModel::create(['name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest([
            'name' => ' Testing ',
        ])
        ->allowedFilters(AllowedFilter::exact('name'))
        ->get();

    $this->assertCount(0, $modelsResult);
});

it('can filter results by scope', function () {
    $testModel = TestModel::create(['name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest(['named' => 'John Testing Doe'])
        ->allowedFilters(AllowedFilter::scope('named'))
        ->get();

    $this->assertCount(1, $modelsResult);
});

it('can filter results by nested relation scope', function () {
    $testModel = TestModel::create(['name' => 'John Testing Doe']);

    $testModel->relatedModels()->create(['name' => 'John\'s Post']);

    $modelsResult = createQueryFromFilterRequest(['relatedModels.named' => 'John\'s Post'])
        ->allowedFilters(AllowedFilter::scope('relatedModels.named'))
        ->get();

    $this->assertCount(1, $modelsResult);
});

it('can filter results by type hinted scope', function () {
    TestModel::create(['name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest(['user' => 1])
        ->allowedFilters(AllowedFilter::scope('user'))
        ->get();

    $this->assertCount(1, $modelsResult);
});

it('can filter results by regular and type hinted scope', function () {
    TestModel::create(['id' => 1000, 'name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest(['user_info' => ['id' => '1000', 'name' => 'John Testing Doe']])
        ->allowedFilters(AllowedFilter::scope('user_info'))
        ->get();

    $this->assertCount(1, $modelsResult);
});

it('can filter results by scope with multiple parameters', function () {
    Carbon::setTestNow(Carbon::parse('2016-05-05'));

    $testModel = TestModel::create(['name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest(['created_between' => '2016-01-01,2017-01-01'])
        ->allowedFilters(AllowedFilter::scope('created_between'))
        ->get();

    $this->assertCount(1, $modelsResult);
});

it('can filter results by scope with multiple parameters in an associative array', function () {
    Carbon::setTestNow(Carbon::parse('2016-05-05'));

    $testModel = TestModel::create(['name' => 'John Testing Doe']);

    $modelsResult = createQueryFromFilterRequest(['created_between' => ['start' => '2016-01-01', 'end' => '2017-01-01']])
        ->allowedFilters(AllowedFilter::scope('created_between'))
        ->get();

    $this->assertCount(1, $modelsResult);
});

it('can filter results by a custom filter class', function () {
    $testModel = $this->models->first();

    $filterClass = new class () implements FilterInterface {
        public function __invoke(Builder $query, $value, string $property): Builder
        {
            return $query->where('name', $value);
        }
    };

    $modelResult = createQueryFromFilterRequest([
            'custom_name' => $testModel->name,
        ])
        ->allowedFilters(AllowedFilter::custom('custom_name', $filterClass))
        ->first();

    $this->assertEquals($testModel->id, $modelResult->id);
});

it('can allow multiple filters', function () {
    $model1 = TestModel::create(['name' => 'abcdef']);
    $model2 = TestModel::create(['name' => 'abcdef']);

    $results = createQueryFromFilterRequest([
            'name' => 'abc',
        ])
        ->allowedFilters('name', AllowedFilter::exact('id'))
        ->get();

    $this->assertCount(2, $results);
    $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
});

it('can allow multiple filters as an array', function () {
    $model1 = TestModel::create(['name' => 'abcdef']);
    $model2 = TestModel::create(['name' => 'abcdef']);

    $results = createQueryFromFilterRequest([
            'name' => 'abc',
        ])
        ->allowedFilters(['name', AllowedFilter::exact('id')])
        ->get();

    $this->assertCount(2, $results);
    $this->assertEquals([$model1->id, $model2->id], $results->pluck('id')->all());
});

it('can filter by multiple filters', function () {
    $model1 = TestModel::create(['name' => 'abcdef']);
    $model2 = TestModel::create(['name' => 'abcdef']);

    $results = createQueryFromFilterRequest([
            'name' => 'abc',
            'id' => "1,{$model1->id}",
        ])
        ->allowedFilters('name', AllowedFilter::exact('id'))
        ->get();

    $this->assertCount(1, $results);
    $this->assertEquals([$model1->id], $results->pluck('id')->all());
});

it('guards against invalid filters', function () {
    $this->expectException(InvalidFilterQuery::class);

    createQueryFromFilterRequest(['name' => 'John'])
        ->allowedFilters('id');
});

it('does not throw invalid filter exception when disable in config', function () {
    config(['query-builder.disable_invalid_filter_query_exception' => true]);

    createQueryFromFilterRequest(['name' => 'John'])
        ->allowedFilters('id');

    $this->assertTrue(true);
});

it('can create a custom filter with an instantiated filter', function () {
    $customFilter = new class ('test1') implements CustomFilter {
        /** @var string */
        protected $filter;

        public function __construct(string $filter) {
            $this->filter = $filter;
        }

        public function __invoke(Builder $query, $value, string $property): Builder
        {
            return $query;
        }
    };

    TestModel::create(['name' => 'abcdef']);

    $results = createQueryFromFilterRequest([
            '*' => '*',
        ])
        ->allowedFilters('name', AllowedFilter::custom('*', $customFilter))
        ->get();

    $this->assertNotEmpty($results);
});

test('an invalid filter query exception contains the unknown and allowed filters', function () {
    $exception = new InvalidFilterQuery(collect(['unknown filter']), collect(['allowed filter']));

    $this->assertEquals(['unknown filter'], $exception->unknownFilters->all());
    $this->assertEquals(['allowed filter'], $exception->allowedFilters->all());
});

it('allows for adding ignorable values', function () {
    $shouldBeIgnored = ['', '-1', null, 'ignored_string', 'another_ignored_string'];

    $filter = AllowedFilter::exact('name')->ignore($shouldBeIgnored[0]);
    $filter
        ->ignore($shouldBeIgnored[1], $shouldBeIgnored[2])
        ->ignore([$shouldBeIgnored[3], $shouldBeIgnored[4]]);

    $valuesIgnoredByFilter = $filter->getIgnored();

    $this->assertEquals(sort($shouldBeIgnored), sort($valuesIgnoredByFilter));
});

it('should not apply a filter if the supplied value is ignored', function () {
    $models = createQueryFromFilterRequest([
            'name' => '-1',
        ])
        ->allowedFilters(AllowedFilter::exact('name')->ignore('-1'))
        ->get();

    $this->assertCount(TestModel::count(), $models);
});

it('should apply the filter on the subset of allowed values', function () {
    TestModel::create(['name' => 'John Doe']);
    TestModel::create(['name' => 'John Deer']);

    $models = createQueryFromFilterRequest([
            'name' => 'John Deer,John Doe',
        ])
        ->allowedFilters(AllowedFilter::exact('name')->ignore('John Deer'))
        ->get();

    $this->assertCount(1, $models);
});

it('can take an argument for custom column name resolution', function () {
    $filter = AllowedFilter::custom('property_name', new FiltersExact(), 'property_column_name');

    $this->assertInstanceOf(AllowedFilter::class, $filter);
    $this->assertClassHasAttribute('internalName', get_class($filter));
});

it('sets property column name to property name by default', function () {
    $filter = AllowedFilter::custom('property_name', new FiltersExact());

    $this->assertEquals($filter->getName(), $filter->getInternalName());
});

it('resolves queries using property column name', function () {
    $filter = AllowedFilter::custom('nickname', new FiltersExact(), 'name');

    TestModel::create(['name' => 'abcdef']);

    $models = createQueryFromFilterRequest([
            'nickname' => 'abcdef',
        ])
        ->allowedFilters($filter)
        ->get();

    $this->assertCount(1, $models);
});

it('can filter using boolean flags', function () {
    TestModel::query()->update(['is_visible' => true]);
    $filter = AllowedFilter::exact('is_visible');

    $models = createQueryFromFilterRequest(['is_visible' => 'false'])
        ->allowedFilters($filter)
        ->get();

    $this->assertCount(0, $models);
    $this->assertGreaterThan(0, TestModel::all()->count());
});

it('should apply a default filter value if nothing in request', function () {
    TestModel::create(['name' => 'UniqueJohn Doe']);
    TestModel::create(['name' => 'UniqueJohn Deer']);

    $models = createQueryFromFilterRequest([])
        ->allowedFilters(AllowedFilter::partial('name')->default('UniqueJohn'))
        ->get();

    $this->assertEquals(2, $models->count());
});

it('does not apply default filter when filter exists and default is set', function () {
    TestModel::create(['name' => 'UniqueJohn UniqueDoe']);
    TestModel::create(['name' => 'UniqueJohn Deer']);

    $models = createQueryFromFilterRequest([
            'name' => 'UniqueDoe',
        ])
        ->allowedFilters(AllowedFilter::partial('name')->default('UniqueJohn'))
        ->get();

    $this->assertEquals(1, $models->count());
});

it('can override the array value delimiter for single filters', function () {
    TestModel::create(['name' => '>XZII/Q1On']);
    TestModel::create(['name' => 'h4S4MG3(+>azv4z/I<o>']);

    // First use default delimiter
    $models = createQueryFromFilterRequest([
            'ref_id' => 'h4S4MG3(+>azv4z/I<o>,>XZII/Q1On',
        ])
        ->allowedFilters(AllowedFilter::exact('ref_id', 'name', true))
        ->get();
    $this->assertEquals(2, $models->count());

    // Custom delimiter
    $models = createQueryFromFilterRequest([
            'ref_id' => 'h4S4MG3(+>azv4z/I<o>|>XZII/Q1On',
        ])
        ->allowedFilters(AllowedFilter::exact('ref_id', 'name', true, '|'))
        ->get();
    $this->assertEquals(2, $models->count());

    // Custom delimiter, but default in request
    $models = createQueryFromFilterRequest([
            'ref_id' => 'h4S4MG3(+>azv4z/I<o>,>XZII/Q1On',
        ])
        ->allowedFilters(AllowedFilter::exact('ref_id', 'name', true, '|'))
        ->get();
    $this->assertEquals(0, $models->count());
});

// Helpers
function createQueryFromFilterRequest(array $filters): QueryBuilder
{
    $request = new Request([
        'filter' => $filters,
    ]);

    return QueryBuilder::for(TestModel::class, $request);
}
