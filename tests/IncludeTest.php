<?php

namespace Spatie\QueryBuilder\Tests;

use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Tests\Models\MorphModel;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;

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
            ->allowedIncludes('related-models')
            ->get();

        $this->assertCount(TestModel::count(), $models);
    }

    /** @test */
    public function it_can_include_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-models')
            ->allowedIncludes('related-models')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_include_nested_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-models.nested-related-models')
            ->allowedIncludes('related-models.nested-related-models')
            ->get();

        $models->each(function (Model $model) {
            $this->assertRelationLoaded($model->relatedModels, 'nestedRelatedModels');
        });
    }

    /** @test */
    public function it_can_include_model_relations_from_nested_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-models')
            ->allowedIncludes('related-models.nested-related-models')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_include_morph_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('morph-models')
            ->allowedIncludes('morph-models')
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
    public function it_can_include_case_insensitive()
    {
        $models = $this
            ->createQueryFromIncludeRequest('RelaTed-Models')
            ->allowedIncludes('related-models')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_include_models_on_an_empty_collection()
    {
        TestModel::query()->delete();

        $models = $this
            ->createQueryFromIncludeRequest('related-models')
            ->allowedIncludes('related-models')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_guards_against_invalid_includes()
    {
        $this->expectException(InvalidIncludeQuery::class);

        $this
            ->createQueryFromIncludeRequest('random-model')
            ->allowedIncludes('related-models');
    }

    /** @test */
    public function it_can_allow_multiple_includes()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-models')
            ->allowedIncludes('related-models', 'other-related-models')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_allow_multiple_includes_as_an_array()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-models')
            ->allowedIncludes(['related-models', 'other-related-models'])
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
    }

    /** @test */
    public function it_can_remove_duplicate_includes_from_nested_includes()
    {
        $query = $this
            ->createQueryFromIncludeRequest('related-models')
            ->allowedIncludes('related-models.nested-related-models', 'related-models');

        $property = (new ReflectionClass($query))->getProperty('allowedIncludes');
        $property->setAccessible(true);

        $includes = $property->getValue($query);

        $this->assertCount(2, $includes);
    }

    /** @test */
    public function it_can_include_multiple_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-models,other-related-models')
            ->allowedIncludes(['related-models', 'other-related-models'])
            ->get();

        $this->assertRelationLoaded($models, 'relatedModels');
        $this->assertRelationLoaded($models, 'otherRelatedModels');
    }

    /** @test */
    public function it_can_query_included_many_to_many_relationships()
    {
        DB::enableQueryLog();

        $this
            ->createQueryFromIncludeRequest('related-through-pivot-models')
            ->allowedIncludes('related-through-pivot-models')
            ->get();

        // Based on the following query: TestModel::with('relatedThroughPivotModels')->get();
        // Without where-clause as that differs per Laravel version
        $this->assertQueryLogContains('select "related_through_pivot_models".*, "pivot_models"."test_model_id" as "pivot_test_model_id", "pivot_models"."related_through_pivot_model_id" as "pivot_related_through_pivot_model_id" from "related_through_pivot_models" inner join "pivot_models" on "related_through_pivot_models"."id" = "pivot_models"."related_through_pivot_model_id"');
    }

    /** @test */
    public function it_returns_correct_id_when_including_many_to_many_relationship()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-through-pivot-models')
            ->allowedIncludes('related-through-pivot-models')
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
