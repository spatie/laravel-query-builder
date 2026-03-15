# Upgrading

## From v6 to v7

### Requirements

- PHP 8.3+
- Laravel 12 or 13

Support for Laravel 10 and 11 has been dropped.

### Wildcard support removed

The wildcard (`'*'`) parameter for `allowedFilters()`, `allowedSorts()`, and `allowedIncludes()` has been removed. You must now explicitly list all allowed filters, sorts, and includes. This prevents accidentally exposing database columns or relationships to API consumers.

```php
// Before
QueryBuilder::for(User::class)->allowedFilters('*');

// After
QueryBuilder::for(User::class)->allowedFilters('name', 'email');
```

### Variadic parameters

The `allowedFilters()`, `allowedSorts()`, `allowedIncludes()`, `allowedFields()`, `defaultSort()`, and `defaultSorts()` methods now accept variadic arguments instead of arrays.

```php
// Before
QueryBuilder::for(User::class)
    ->allowedFilters(['name', 'email'])
    ->allowedSorts(['name'])
    ->allowedIncludes(['posts'])
    ->allowedFields(['id', 'name']);

// After
QueryBuilder::for(User::class)
    ->allowedFilters('name', 'email')
    ->allowedSorts('name')
    ->allowedIncludes('posts')
    ->allowedFields('id', 'name');
```

If you have dynamic arrays, use the spread operator:

```php
$filters = ['name', 'email'];
QueryBuilder::for(User::class)->allowedFilters(...$filters);
```

### `SortDirection` is now an enum

The `SortDirection` class with string constants has been replaced with a proper PHP enum.

```php
// Before
use Spatie\QueryBuilder\Enums\SortDirection;

AllowedSort::field('name')->defaultDirection(SortDirection::DESCENDING);

// After
use Spatie\QueryBuilder\Enums\SortDirection;

AllowedSort::field('name')->defaultDirection(SortDirection::Descending);
```

The `AllowedSort::defaultDirection()` method now requires a `SortDirection` enum value instead of a string.

### Filter renames

`AllowedFilter::beginsWithStrict()` has been renamed to `AllowedFilter::beginsWith()`.
`AllowedFilter::endsWithStrict()` has been renamed to `AllowedFilter::endsWith()`.

```php
// Before
AllowedFilter::beginsWithStrict('name');
AllowedFilter::endsWithStrict('name');

// After
AllowedFilter::beginsWith('name');
AllowedFilter::endsWith('name');
```

### `AllowedInclude` factory methods now return `self` instead of `Collection`

`AllowedInclude::relationship()`, `AllowedInclude::count()`, `AllowedInclude::exists()`, `AllowedInclude::callback()`, and `AllowedInclude::custom()` now return a single `AllowedInclude` instance instead of a `Collection`. This should not affect most usage since you typically pass them to `allowedIncludes()`.

### Static delimiter methods removed

The static delimiter methods on `QueryBuilderRequest` have been removed (`setArrayValueDelimiter`, `setFilterArrayValueDelimiter`, `setSortsArrayValueDelimiter`, `setIncludesArrayValueDelimiter`, `setFieldsArrayValueDelimiter`, `setAppendsArrayValueDelimiter`, `resetDelimiters`).

Delimiters are now configured via the `delimiter` key in the config file:

```php
// config/query-builder.php
'delimiter' => ',',
```

The `$arrayValueDelimiter` parameter has also been removed from all `AllowedFilter` factory methods.

### `allowedFields()` no longer needs to be called before `allowedIncludes()`

The `AllowedFieldsMustBeCalledBeforeAllowedIncludes` exception has been removed. You can now call `allowedFields()` and `allowedIncludes()` in any order.

### Config changes

The `disable_invalid_includes_query_exception` config key has been renamed to `disable_invalid_include_query_exception` (singular "include").

The `convert_relation_table_name_strategy` config now uses `null` instead of `false` as its default/disabled value.

A new `delimiter` config key has been added (default: `','`).

The `count_suffix` and `exists_suffix` config keys have been consolidated into a single `suffixes` array, which also includes the new aggregate suffixes:

```php
// Before
'count_suffix' => 'Count',
'exists_suffix' => 'Exists',

// After
'suffixes' => [
    'count' => 'Count',
    'exists' => 'Exists',
    'min' => 'Min',
    'max' => 'Max',
    'sum' => 'Sum',
    'avg' => 'Avg',
],
```

### Filter interface return type

The `Filter` interface's `__invoke` method now has an explicit `void` return type. If you have custom filter classes, update them:

```php
// Before
public function __invoke(Builder $query, $value, string $property)

// After
public function __invoke(Builder $query, mixed $value, string $property): void
```

The same applies to the `Sort` interface and `IncludeInterface`.

### Filter class hierarchy refactored

`FiltersPartial` no longer extends `FiltersExact`, and `FiltersOperator` no longer extends `FiltersExact`. If you were extending these classes and relying on the inheritance chain, note that relation constraint handling is now provided via the `HandlesRelationConstraints` trait in `Spatie\QueryBuilder\Filters\Concerns`.

### PHPStan level raised to 6

The PHPStan analysis level has been bumped from 5 to 6.

## From v5 to v6

A lot of the query builder classes now have typed properties and method parameters. If you have any custom sorts, includes, or filters, you will need to specify the property and parameter types used.

## Notice when upgrading to 5.6.0

The changes to the `default()` method break backwards compatibility when setting the default value to `null` (`default(null)`). This is pretty much an edge case, but if you're trying to unset the default value, you can use the `unsetDefault()` method instead. 

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
