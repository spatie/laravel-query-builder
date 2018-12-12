<?php

namespace Spatie\QueryBuilder\Tests;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\Models\AppendModel;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;

class AppendTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        factory(AppendModel::class, 5)->create();
    }

    /** @test */
    public function it_does_not_require_appends()
    {
        $models = QueryBuilder::for(AppendModel::class, new Request())
            ->allowedAppends('fullname')
            ->get();

        $this->assertCount(AppendModel::count(), $models);
    }

    /** @test */
    public function it_can_append_attributes()
    {
        $model = $this
            ->createQueryFromAppendRequest('fullname')
            ->allowedAppends('fullname')
            ->first();

        $this->assertAttributeLoaded($model, 'fullname');
    }

    /** @test */
    public function it_can_append_case_insensitive()
    {
        $model = $this
            ->createQueryFromAppendRequest('FullName')
            ->allowedAppends('fullname')
            ->first();

        $this->assertAttributeLoaded($model, 'fullname');
    }

    /** @test */
    public function it_guards_against_invalid_appends()
    {
        $this->expectException(InvalidAppendQuery::class);

        $this
            ->createQueryFromAppendRequest('random-attribute-to-append')
            ->allowedAppends('attribute-to-append');
    }

    /** @test */
    public function it_can_allow_multiple_appends()
    {
        $model = $this
            ->createQueryFromAppendRequest('fullname')
            ->allowedAppends('fullname', 'randomAttribute')
            ->first();

        $this->assertAttributeLoaded($model, 'fullname');
    }

    /** @test */
    public function it_can_allow_multiple_appends_as_an_array()
    {
        $model = $this
            ->createQueryFromAppendRequest('fullname')
            ->allowedAppends(['fullname', 'randomAttribute'])
            ->first();

        $this->assertAttributeLoaded($model, 'fullname');
    }

    /** @test */
    public function it_can_append_multiple_attributes()
    {
        $model = $this
            ->createQueryFromAppendRequest('fullname,reversename')
            ->allowedAppends(['fullname', 'reversename'])
            ->first();

        $this->assertAttributeLoaded($model, 'fullname');
        $this->assertAttributeLoaded($model, 'reversename');
    }

    /** @test */
    public function an_invalid_append_query_exception_contains_the_not_allowed_and_allowed_appends()
    {
        $exception = new InvalidAppendQuery(collect(['not allowed append']), collect(['allowed append']));

        $this->assertEquals(['not allowed append'], $exception->appendsNotAllowed->all());
        $this->assertEquals(['allowed append'], $exception->allowedAppends->all());
    }

    protected function createQueryFromAppendRequest(string $appends): QueryBuilder
    {
        $request = new Request([
            'append' => $appends,
        ]);

        return QueryBuilder::for(AppendModel::class, $request);
    }

    protected function assertAttributeLoaded(AppendModel $model, string $attribute)
    {
        $this->assertTrue(array_key_exists($attribute, $model->toArray()));
    }

    /** @test */
    public function it_allows_callbacks_for_when_relations_are_appended()
    {
        $builder = $this->createQueryFromAppendRequest('fullname');

        $callbackMock = $this->makeCallbackMock();
        $callbackMock
            ->expects($this->once())
            ->method('__invoke')
            ->with($builder);

        $builder
            ->allowedAppends('fullname')
            ->whenAppended('fullname', $callbackMock);
    }

    private function makeCallbackMock()
    {
        return $this->createPartialMock(\stdClass::class, ['__invoke']);
    }

    /** @test */
    public function it_allows_for_a_list_of_appends_to_trigger_a_the_callback()
    {
        $builder = $this->createQueryFromAppendRequest('fullname,reversename');

        $shouldNotBeInvoked = $this->makeCallbackMock();
        $shouldNotBeInvoked->expects($this->never())->method('__invoke');

        $shouldBeInvoked = $this->makeCallbackMock();
        $shouldBeInvoked->expects($this->once())->method('__invoke');

        $builder
            ->allowedAppends('fullname', 'reversename')
            ->whenAppended(['fullname', 'reversename'], $shouldBeInvoked)
            ->whenAppended('fullname', $shouldNotBeInvoked)
            ->whenAppended('reversename', $shouldBeInvoked);
    }

    /** @test */
    public function it_allows_for_a_wildcard_callback()
    {
        $builder = $this->createQueryFromAppendRequest('fullname');

        $shouldBeInvoked = $this->makeCallbackMock();
        $shouldBeInvoked->expects($this->once())->method('__invoke');

        $builder
            ->allowedAppends('fullname')
            ->whenAppended('*', $shouldBeInvoked);
    }

    /** @test */
    public function it_does_not_fire_for_a_wildcard_if_nothing_is_appended()
    {
        $builder = QueryBuilder::for(AppendModel::class, new Request());

        $shouldNotBeInvoked = $this->makeCallbackMock();
        $shouldNotBeInvoked->expects($this->never())->method('__invoke');

        $builder
            ->allowedAppends('fullname')
            ->whenAppended('*', $shouldNotBeInvoked);
    }

    /** @test */
    public function it_throws_an_exception_the_suggested_appends_are_not_allowed()
    {
        $builder = $this->createQueryFromAppendRequest('fullname');

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Appending of required appends [reversename, lastname] for callback is not allowed on builder instance!');

        $builder
            ->allowedAppends('fullname')
            ->whenAppended(['fullname', 'reversename', 'lastname'], $this->makeCallbackMock());
    }
}
