<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Includes\IncludedCount;
use Spatie\QueryBuilder\Includes\IncludeInterface;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\MorphModel;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

class IncludeTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp(): void
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 5)->create();

        $this->models->each(function (TestModel $model) {
            $model
                ->relatedModels()->create(['name' => 'Test'])
                ->nestedRelatedModels()->create(['name' => 'Test']);

            $model->morphModels()->create(['name' => 'Test']);

            $model->relatedThroughPivotModels()->create([
                'id' => $model->id + 1,
                'name' => 'Test',
            ]);
        });
    }

    /** @test */
    public function it_does_not_require_includes()
    {
        $models = QueryBuilder::for(TestModel::class, new Request())
            ->allowedIncludes('relatedModels')
            ->get();

        $this->assertCount(TestModel::count(), $models);
    }

    /** @test */
    public function it_can_handle_empty_includes()
    {
        $models = QueryBuilder::for(TestModel::class, new Request())
            ->allowedIncludes([
                null,
                [],
                '',
            ])
            ->get();

        $this->assertCount(TestModel::count(), $models);
    }

    /** @test */
    public function it_can_include_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes('relatedModels')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_include_model_relations_by_alias()
    {
        $models = $this
            ->createQueryFromIncludeRequest('include-alias')
            ->allowedIncludes(AllowedInclude::relationship('include-alias', 'relatedModels'))
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_include_an_includes_count()
    {
        $model = $this
            ->createQueryFromIncludeRequest('relatedModelsCount')
            ->allowedIncludes('relatedModelsCount')
            ->first();

        $this->assertNotNull($model->related_models_count);
    }

    /** @test */
    public function allowing_an_include_also_allows_the_include_count()
    {
        $model = $this
            ->createQueryFromIncludeRequest('relatedModelsCount')
            ->allowedIncludes('relatedModels')
            ->first();

        $this->assertNotNull($model->related_models_count);
    }

    /** @test */
    public function it_can_include_nested_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedModels.nestedRelatedModels')
            ->allowedIncludes('relatedModels.nestedRelatedModels')
            ->get();

        $models->each(function (Model $model) {
            $this->assertRelationLoaded($model->relatedModels, 'nestedRelatedModels');
        });
    }

    /** @test */
    public function it_can_include_nested_model_relations_by_alias()
    {
        $this->markTestSkipped('See #522');

        $models = $this
            ->createQueryFromIncludeRequest('nested-alias')
            ->allowedIncludes(
                AllowedInclude::relationship('nested-alias', 'relatedModels.nestedRelatedModels')
            )
            ->get();

        $models->each(function (Model $model) {
            $this->assertRelationLoaded($model->relatedModels, 'nestedRelatedModels');
        });
    }

    /** @test */
    public function it_can_include_model_relations_from_nested_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes('relatedModels.nestedRelatedModels')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function allowing_a_nested_include_only_allows_the_include_count_for_the_first_level()
    {
        $model = $this
            ->createQueryFromIncludeRequest('relatedModelsCount')
            ->allowedIncludes('relatedModels.nestedRelatedModels')
            ->first();

        $this->assertNotNull($model->related_models_count);

        $this->expectException(InvalidIncludeQuery::class);

        $this
            ->createQueryFromIncludeRequest('nestedRelatedModelsCount')
            ->allowedIncludes('relatedModels.nestedRelatedModels')
            ->first();

        $this->expectException(InvalidIncludeQuery::class);

        $this
            ->createQueryFromIncludeRequest('related-models.nestedRelatedModelsCount')
            ->allowedIncludes('relatedModels.nestedRelatedModels')
            ->first();
    }

    /** @test */
    public function it_can_include_morph_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('morphModels')
            ->allowedIncludes('morphModels')
            ->get();

        $this->assertRelationLoaded($models, 'morphModels');
    }

    /** @test */
    public function it_can_include_reverse_morph_model_relations()
    {
        $request = new Request([
            'include' => 'parent',
        ]);

        $models = QueryBuilder::for(MorphModel::class, $request)
            ->allowedIncludes('parent')
            ->get();

        $this->assertRelationLoaded($models, 'parent');
    }

    /** @test */
    public function it_can_include_camel_case_includes()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes('relatedModels')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_include_models_on_an_empty_collection()
    {
        TestModel::query()->delete();

        $models = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes('relatedModels')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_guards_against_invalid_includes()
    {
        $this->expectException(InvalidIncludeQuery::class);

        $this
            ->createQueryFromIncludeRequest('random-model')
            ->allowedIncludes('relatedModels');
    }

    /** @test */
    public function it_can_allow_multiple_includes()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes('relatedModels', 'otherRelatedModels')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_allow_multiple_includes_as_an_array()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes(['relatedModels', 'otherRelatedModels'])
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_remove_duplicate_includes_from_nested_includes()
    {
        $query = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes('relatedModels.nestedRelatedModels', 'relatedModels');

        $property = (new ReflectionClass($query))->getProperty('allowedIncludes');
        $property->setAccessible(true);

        $includes = $property->getValue($query)->map(function (AllowedInclude $allowedInclude) {
            return $allowedInclude->getName();
        });

        $this->assertTrue($includes->contains('relatedModels'));
        $this->assertTrue($includes->contains('relatedModelsCount'));
        $this->assertTrue($includes->contains('relatedModels.nestedRelatedModels'));
    }

    /** @test */
    public function it_can_include_multiple_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedModels,otherRelatedModels')
            ->allowedIncludes(['relatedModels', 'otherRelatedModels'])
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
        $this->assertRelationLoaded($models, 'otherRelatedModels');
    }

    /** @test */
    public function it_can_query_included_many_to_many_relationships()
    {
        DB::enableQueryLog();

        $this
            ->createQueryFromIncludeRequest('relatedThroughPivotModels')
            ->allowedIncludes('relatedThroughPivotModels')
            ->get();

        // Based on the following query: TestModel::with('relatedThroughPivotModels')->get();
        // Without where-clause as that differs per Laravel version
        //dump(DB::getQueryLog());
        $this->assertQueryLogContains('select `related_through_pivot_models`.*, `pivot_models`.`test_model_id` as `pivot_test_model_id`, `pivot_models`.`related_through_pivot_model_id` as `pivot_related_through_pivot_model_id` from `related_through_pivot_models` inner join `pivot_models` on `related_through_pivot_models`.`id` = `pivot_models`.`related_through_pivot_model_id` where `pivot_models`.`test_model_id` in (1, 2, 3, 4, 5)');
    }

    /** @test */
    public function it_returns_correct_id_when_including_many_to_many_relationship()
    {
        $models = $this
            ->createQueryFromIncludeRequest('relatedThroughPivotModels')
            ->allowedIncludes('relatedThroughPivotModels')
            ->get();

        $relatedModel = $models->first()->relatedThroughPivotModels->first();

        $this->assertEquals($relatedModel->id, $relatedModel->pivot->related_through_pivot_model_id);
    }

    /** @test */
    public function an_invalid_include_query_exception_contains_the_unknown_and_allowed_includes()
    {
        $exception = new InvalidIncludeQuery(collect(['unknown include']), collect(['allowed include']));

        $this->assertEquals(['unknown include'], $exception->unknownIncludes->all());
        $this->assertEquals(['allowed include'], $exception->allowedIncludes->all());
    }

    /** @test */
    public function it_can_alias_multiple_allowed_includes()
    {
        $request = new Request([
            'include' => 'relatedModelsCount,relationShipAlias',
        ]);

        $models = QueryBuilder::for(TestModel::class, $request)
            ->allowedIncludes([
                AllowedInclude::count('relatedModelsCount'),
                AllowedInclude::relationship('relationShipAlias', 'otherRelatedModels'),
            ])
            ->get();

        $this->assertRelationLoaded($models, 'otherRelatedModels');
        $models->each(function ($model) {
            $this->assertNotNull($model->related_models_count);
        });
    }

    /** @test */
    public function it_can_include_custom_include_class()
    {
        $includeClass = new class() implements IncludeInterface {
            public function __invoke(Builder $query, string $include): Builder
            {
                // TODO:
                // when drop laravel 6 and 7
                // use withAggregate instead withCount
                return $query->withCount($include);
            }
        };

        $modelResult = $this
            ->createQueryFromIncludeRequest('relatedModels')
            ->allowedIncludes(AllowedInclude::custom('relatedModels', $includeClass))
            ->first();

        $this->assertNotNull($modelResult->related_models_count);
    }

    /** @test */
    public function it_can_include_custom_include_class_by_alias()
    {
        $includeClass = new class() implements IncludeInterface {
            public function __invoke(Builder $query, string $include): Builder
            {
                // TODO:
                // when drop laravel 6 and 7
                // use withAggregate instead withCount
                return $query->withCount($include);
            }
        };

        $modelResult = $this
            ->createQueryFromIncludeRequest('relatedModelsCount')
            ->allowedIncludes(AllowedInclude::custom('relatedModelsCount', $includeClass, 'relatedModels'))
            ->first();

        $this->assertNotNull($modelResult->related_models_count);
    }

    /** @test */
    public function it_can_take_an_argument_for_custom_column_name_resolution()
    {
        $include = AllowedInclude::custom('property_name', new IncludedCount(), 'property_column_name');

        $this->assertInstanceOf(Collection::class, $include);
        $this->assertInstanceOf(AllowedInclude::class, $include->first());
        $this->assertClassHasAttribute('internalName', get_class($include->first()));
    }

    /** @test */
    public function it_can_include_a_custom_base_query_with_select()
    {
        $request = new Request([
            'include' => 'relatedModelsCount',
        ]);

        $modelResult = QueryBuilder::for(TestModel::select('id', 'name'), $request)
            ->allowedIncludes(AllowedInclude::custom('relatedModelsCount', new IncludedCount(), 'relatedModels'))
            ->first();

        $this->assertNotNull($modelResult->related_models_count);
    }

    protected function createQueryFromIncludeRequest(string $includes): QueryBuilder
    {
        $request = new Request([
            'include' => $includes,
        ]);

        return QueryBuilder::for(TestModel::class, $request);
    }

    protected function assertRelationLoaded(Collection $collection, string $relation)
    {
        $hasModelWithoutRelationLoaded = $collection
            ->contains(function (Model $model) use ($relation) {
                return ! $model->relationLoaded($relation);
            });

        $this->assertFalse($hasModelWithoutRelationLoaded, "The `{$relation}` relation was expected but not loaded.");
    }
}
