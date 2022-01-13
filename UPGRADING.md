# Upgrading

## From v4 to v5

This version adds support for Laravel 9 and drops support for all older version.

Appending attributes to a query was removed to make package maintenance easier. The rest of the public API was not changed, so you'll be able to upgrade without making any changes.

## From v3 to v4

The biggest change in v4 is the way requested filters, includes and fields are processed. In previous versions we would automatically camel-case relationship names for includes and nested filters. Requested (nested) fields would also be transformed to their plural snake-case form, regardless of what was actually requested.

In v4 we've removed this behaviour and will instead always pass the requested filter, include or field from the request URL to the query.

When following Laravel's convention of camelcase relationship names, a request will look like this:

```
GET /api/users
        ?include=latestPosts,friendRequests
        &filter[homeAddress.city]=Antwerp
        &fields[related_models.test_models]=id,name
```

A minimal `QueryBuilder` for the above request looks like this:

```php
use Spatie\QueryBuilder\QueryBuilder;

QueryBuilder::for(User::class)
    ->allowedIncludes(['latestPosts', 'friendRequests'])
    ->allowedFilters(['homeAddress.city'])
    ->allowedFields(['related_models.test_models.id', 'related_models.test_models.name']);
```

There is no automated upgrade path available at this time.

## From v2 to v3

Possible changes in this version due to internal changes.

The package's `Spatie\QueryBuilder\QueryBuilder` class no longer extends Laravel's `Illuminate\Database\Eloquent\Builder`. This means you can no longer pass a `QueryBuilder` instance where a `Illuminate\Database\Eloquent\Builder` instance is expected. However, all Eloquent method calls get forwarded to the internal `Illuminate\Database\Eloquent\Builder`.

Using `$queryBuilder->getEloquentBuilder()` you can access the internal `Illuminate\Database\Eloquent\Builder`.

## From v1 to v2

There are a lot of renamed methods and classes in this release. An advanced IDE like PhpStorm is recommended to rename these methods and classes in your code base. Use the refactor -> rename functionality instead of find & replace.

- rename `Spatie\QueryBuilder\Sort` to `Spatie\QueryBuilder\AllowedSort`
- rename `Spatie\QueryBuilder\Included` to `Spatie\QueryBuilder\AllowedInclude`
- rename `Spatie\QueryBuilder\Filter` to `Spatie\QueryBuilder\AllowedFilter`
- replace request macro's like `request()->filters()`, `request()->includes()`, etc... with their related methods on the `QueryBuilderRequest` class. This class needs to be instantiated with a request object, (more info here: https://github.com/spatie/laravel-query-builder/issues/328):
    * `request()->includes()` -> `QueryBuilderRequest::fromRequest($request)->includes()`
    * `request()->filters()` -> `QueryBuilderRequest::fromRequest($request)->filters()`
    * `request()->sorts()` -> `QueryBuilderRequest::fromRequest($request)->sorts()`
    * `request()->fields()` -> `QueryBuilderRequest::fromRequest($request)->fields()`
    * `request()->appends()` -> `QueryBuilderRequest::fromRequest($request)->appends()`
- please note that the above methods on `QueryBuilderRequest` do not take any arguments. You can use the `contains` to check for a certain filter/include/sort/...
- make sure the second argument for `AllowedSort::custom()` is an instance of a sort class, not a classname
    * `AllowedSort::custom('name', MySort::class)` -> `AllowedSort::custom('name', new MySort())`
- make sure the second argument for `AllowedFilter::custom()` is an instance of a filter class, not a classname
    * `AllowedFilter::custom('name', MyFilter::class)` -> `AllowedFilter::custom('name', new MyFilter())`
- make sure all required sorts are allowed using `allowedSorts()`
- make sure all required field selects are allowed using `allowedFields()`
- make sure `allowedFields()` is always called before `allowedIncludes()`
