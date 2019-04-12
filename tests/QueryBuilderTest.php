<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\Sorts\Sort;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Tests\Models\ScopeModel;
use Spatie\QueryBuilder\Tests\Models\SoftDeleteModel;

class QueryBuilderTest extends TestCase
{
    /** @test */
    public function it_will_determine_the_request_when_its_not_given()
    {
        $this->getJson('/test-model?sort=name');

        $builder = QueryBuilder::for(TestModel::class);

        $this->assertEquals([
            'direction' => 'asc',
            'column' => 'name',
        ], $builder->getQuery()->orders[0]);
    }

    /** @test */
    public function it_can_be_given_a_custom_base_query_using_where()
    {
        $queryBuilder = QueryBuilder::for(TestModel::where('id', 1));

        $eloquentBuilder = TestModel::where('id', 1);

        $this->assertEquals(
            $eloquentBuilder->toSql(),
            $queryBuilder->toSql()
        );
    }

    /** @test */
    public function it_can_be_given_a_custom_base_query_using_select()
    {
        $queryBuilder = QueryBuilder::for(TestModel::select('id', 'name'));

        $eloquentBuilder = TestModel::select('id', 'name');

        $this->assertEquals(
            $eloquentBuilder->toSql(),
            $queryBuilder->toSql()
        );
    }

    /** @test */
    public function it_can_query_soft_deletes()
    {
        $queryBuilder = QueryBuilder::for(SoftDeleteModel::class);

        $this->models = factory(SoftDeleteModel::class, 5)->create();

        $this->assertCount(5, $queryBuilder->get());

        $this->models[0]->delete();

        $this->assertCount(4, $queryBuilder->get());
        $this->assertCount(5, $queryBuilder->withTrashed()->get());
    }

    /** @test */
    public function it_can_query_global_scopes()
    {
        ScopeModel::create(['name' => 'John Doe']);
        ScopeModel::create(['name' => 'test']);

        // Global scope on ScopeModel excludes models named 'test'
        $this->assertCount(1, QueryBuilder::for(ScopeModel::class)->get());

        $this->assertCount(2, QueryBuilder::for(ScopeModel::query()->withoutGlobalScopes())->get());

        $this->assertCount(2, QueryBuilder::for(ScopeModel::class)->withoutGlobalScopes()->get());
    }

    /** @test */
    public function it_keeps_eager_loaded_relationships_from_the_base_query()
    {
        TestModel::create(['name' => 'John Doe']);

        $baseQuery = TestModel::with('relatedModels');
        $queryBuilder = QueryBuilder::for($baseQuery);

        $this->assertTrue($baseQuery->first()->relationLoaded('relatedModels'));
        $this->assertTrue($queryBuilder->first()->relationLoaded('relatedModels'));
    }

    /** @test */
    public function it_keeps_local_macros_added_to_the_base_query()
    {
        $baseQuery = TestModel::query();

        $baseQuery->macro('customMacro', function ($builder) {
            return $builder->where('name', 'Foo');
        });

        $queryBuilder = QueryBuilder::for($baseQuery);

        $this->assertEquals($baseQuery->customMacro()->toSql(), $queryBuilder->customMacro()->toSql());
    }

    /** @test */
    public function it_keeps_the_on_delete_callback_added_to_the_base_query()
    {
        $baseQuery = TestModel::query();

        $baseQuery->onDelete(function () {
            return 'onDelete called';
        });

        $this->assertEquals('onDelete called', QueryBuilder::for($baseQuery)->delete());
    }

    /** @test */
    public function it_can_query_local_scopes()
    {
        $queryBuilderQuery = QueryBuilder::for(TestModel::class)
            ->named('john')
            ->toSql();

        $expectedQuery = TestModel::query()->where('name', 'john')->toSql();

        $this->assertEquals($expectedQuery, $queryBuilderQuery);
    }

    /** @test */
    public function it_executes_the_same_query_regardless_of_the_order_of_applied_filters_or_sorts()
    {
        $customSort = new class implements Sort {
            public function __invoke(Builder $query, $descending, string $property): Builder
            {
                return $query->join(
                    'related_models',
                    'test_models.id',
                    '=',
                    'related_models.test_model_id'
                )->orderBy('related_models.name', $descending ? 'desc' : 'asc');
            }
        };

        $req = new Request([
            'filter' => ['name' => 'test'],
            'sort' => 'name',
        ]);

        $usingSortFirst = QueryBuilder::for(TestModel::class, $req)
            ->allowedSorts(\Spatie\QueryBuilder\Sort::custom('name', $customSort))
            ->allowedFilters('name')
            ->toSql();

        $usingFilterFirst = QueryBuilder::for(TestModel::class, $req)
            ->allowedFilters('name')
            ->allowedSorts(\Spatie\QueryBuilder\Sort::custom('name', $customSort))
            ->toSql();

        $this->assertEquals($usingSortFirst, $usingFilterFirst);
    }
}
