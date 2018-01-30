<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\Tests\Models\TestModel;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;

class IncludeTest extends TestCase
{
    /** @var \Illuminate\Support\Collection */
    protected $models;

    public function setUp()
    {
        parent::setUp();

        $this->models = factory(TestModel::class, 5)->create();

        $this->models->each(function (TestModel $model) {
            $model->relatedModel()->create(['name' => 'Test']);
        });
    }

    /** @test */
    public function it_does_not_require_includes()
    {
        $models = QueryBuilder::for(TestModel::class, new Request())
            ->allowedIncludes('related-model')
            ->get();

        $this->assertCount(TestModel::count(), $models);
    }

    /** @test */
    public function it_can_include_model_relations()
    {
        $models = $this
            ->createQueryFromIncludeRequest('related-model')
            ->allowedIncludes('related-model')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModel');
    }

    /** @test */
    public function it_can_include_case_insensitive()
    {
        $models = $this
            ->createQueryFromIncludeRequest('RelaTed-Model')
            ->allowedIncludes('related-model')
            ->get();

        $this->assertRelationLoaded($models, 'relatedModel');
    }

    /** @test */
    public function it_can_include_models_on_an_empty_collection()
    {
        TestModel::query()->delete();

        $models = $this
            ->createQueryFromIncludeRequest('related-model')
            ->allowedIncludes('related-model')
            ->get();

        $this->assertCount(0, $models);
    }

    /** @test */
    public function it_guards_against_invalid_includes()
    {
        $this->expectException(InvalidIncludeQuery::class);

        $this
            ->createQueryFromIncludeRequest('random-model')
            ->allowedIncludes('related-model');
    }

    /** @test */
    public function an_invalid_include_query_exception_contains_the_unknown_and_allowed_includes()
    {
        $exception = new InvalidIncludeQuery(collect(['unknown include']), collect(['allowed include']));

        $this->assertEquals(['unknown include'], $exception->getUnknownIncludes()->all());
        $this->assertEquals(['allowed include'], $exception->getAllowedIncludes()->all());
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
