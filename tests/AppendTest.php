<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\AppendModel;

uses(TestCase::class);

beforeEach(function () {
    AppendModel::factory()->count(5)->create();
});

it('does not require appends', function () {
    $models = QueryBuilder::for(AppendModel::class, new Request())
        ->allowedAppends('fullname')
        ->get();

    expect($models)->toHaveCount(AppendModel::count());
});

it('can append attributes', function () {
    $model = createQueryFromAppendRequest('fullname')
        ->allowedAppends('fullname')
        ->first();

    assertAttributeLoaded($model, 'fullname');
});

it('cannot append case insensitive', function () {
    $this->expectException(InvalidAppendQuery::class);

    createQueryFromAppendRequest('FullName')
        ->allowedAppends('fullname')
        ->first();
});

it('can append collections', function () {
    $models = createQueryFromAppendRequest('FullName')
        ->allowedAppends('FullName')
        ->get();

    assertCollectionAttributeLoaded($models, 'FullName');
});

it('can append paginates', function () {
    $models = createQueryFromAppendRequest('FullName')
        ->allowedAppends('FullName')
        ->paginate();

    assertPaginateAttributeLoaded($models, 'FullName');
});

it('can append simple paginates', function () {
    $models = createQueryFromAppendRequest('FullName')
        ->allowedAppends('FullName')
        ->simplePaginate();

    assertPaginateAttributeLoaded($models, 'FullName');
});

it('can append cursor paginates', function () {
    $models = createQueryFromAppendRequest('FullName')
        ->allowedAppends('FullName')
        ->cursorPaginate();

    assertPaginateAttributeLoaded($models, 'FullName');
});

it('guards against invalid appends', function () {
    $this->expectException(InvalidAppendQuery::class);

    createQueryFromAppendRequest('random-attribute-to-append')
        ->allowedAppends('attribute-to-append');
});

it('can allow multiple appends', function () {
    $model = createQueryFromAppendRequest('fullname')
        ->allowedAppends('fullname', 'randomAttribute')
        ->first();

    assertAttributeLoaded($model, 'fullname');
});

it('can allow multiple appends as an array', function () {
    $model = createQueryFromAppendRequest('fullname')
        ->allowedAppends(['fullname', 'randomAttribute'])
        ->first();

    assertAttributeLoaded($model, 'fullname');
});

it('can append multiple attributes', function () {
    $model = createQueryFromAppendRequest('fullname,reversename')
        ->allowedAppends(['fullname', 'reversename'])
        ->first();

    assertAttributeLoaded($model, 'fullname');
    assertAttributeLoaded($model, 'reversename');
});

test('an invalid append query exception contains the not allowed and allowed appends', function () {
    $exception = new InvalidAppendQuery(collect(['not allowed append']), collect(['allowed append']));

    expect($exception->appendsNotAllowed->all())->toEqual(['not allowed append']);
    expect($exception->allowedAppends->all())->toEqual(['allowed append']);
});

// Helpers
function createQueryFromAppendRequest(string $appends): QueryBuilder
{
    $request = new Request([
        'append' => $appends,
    ]);

    return QueryBuilder::for(AppendModel::class, $request);
}

function assertAttributeLoaded(AppendModel $model, string $attribute)
{
    expect(array_key_exists($attribute, $model->toArray()))->toBeTrue();
}

function assertCollectionAttributeLoaded(Collection $collection, string $attribute)
{
    $hasModelWithoutAttributeLoaded = $collection
        ->contains(function (Model $model) use ($attribute) {
            return ! array_key_exists($attribute, $model->toArray());
        });

    test()->assertFalse($hasModelWithoutAttributeLoaded, "The `{$attribute}` attribute was expected but not loaded.");
}

/**
     * @param \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator $collection
     * @param string $attribute
     */
function assertPaginateAttributeLoaded($collection, string $attribute)
{
    $hasModelWithoutAttributeLoaded = $collection
        ->contains(function (Model $model) use ($attribute) {
            return ! array_key_exists($attribute, $model->toArray());
        });

    test()->assertFalse($hasModelWithoutAttributeLoaded, "The `{$attribute}` attribute was expected but not loaded.");
}
