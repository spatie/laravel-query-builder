---
title: Filtering
weight: 1
---

The `filter` query parameters can be used to add `where` clauses to your Eloquent query. Out of the box we support filtering results by partial attribute value, exact attribute value or even if an attribute value exists in a given array of values. For anything more advanced, custom filters can be used.

By default, all filters have to be explicitly allowed using `allowedFilters()`. This method takes an array of strings or `AllowedFilter` instances. An allowed filter can be partial, exact, scope or custom. By default, any string values passed to `allowedFilters()` will automatically be converted to `AllowedFilter::partial()` filters.

## Basic usage

```php
// GET /users?filter[name]=john&filter[email]=gmail

$users = QueryBuilder::for(User::class)
    ->allowedFilters(['name', 'email'])
    ->get();

// $users will contain all users with "john" in their name AND "gmail" in their email address
```

You can specify multiple matching filter values by passing a comma separated list of values:

```php
// GET /users?filter[name]=seb,freek

$users = QueryBuilder::for(User::class)
    ->allowedFilters(['name'])
    ->get();

// $users will contain all users that contain "seb" OR "freek" in their name
```

## Disallowed filters

Finally, when trying to filter on properties that have not been allowed using `allowedFilters()` an `InvalidFilterQuery` exception will be thrown along with a list of allowed filters.

## Exact filters

When filtering IDs, boolean values or a literal string, you'll want to use exact filters. This way `/users?filter[id]=1` won't match all users having the digit `1` in their ID.

Exact filters can be added using `Spatie\QueryBuilder\AllowedFilter::exact('property_name')` in the `allowedFilters()` method.

```php
use Spatie\QueryBuilder\AllowedFilter;

// GET /users?filter[name]=John%20Doe
$users = QueryBuilder::for(User::class)
    ->allowedFilters([AllowedFilter::exact('name')])
    ->get();

// only users with the exact name "John Doe"
```

The query builder will automatically map `1`, `0`, 'true'`, and `'false'` as boolean values and a comma separated list of values as an array:

```php
use Spatie\QueryBuilder\AllowedFilter;

// GET /users?filter[id]=1,2,3,4,5&filter[admin]=true

$users = QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::exact('id'),
        AllowedFilter::exact('admin'),
    ])
    ->get();

// $users will contain all admin users with id 1, 2, 3, 4 or 5
```

## Exact or partial filters for related properties

You can also add filters for a relationship property using the dot-notation: `AllowedFilter::exact('posts.title')`. This works for exact and partial filters. Under the hood we'll add a `whereHas` statement for the `posts` that filters for the given `title` property as well.

In some cases you'll want to disable this behaviour and just pass the raw filter-property value to the query. For example, when using a joined table's value for filtering. By passing `false` as the third parameter to `AllowedFilter::exact()` or `AllowedFilter::partial()` this behaviour can be disabled:

```php
$addRelationConstraint = false;

QueryBuilder::for(User::class)
    ->join('posts', 'posts.user_id', 'users.id')
    ->allowedFilters(AllowedFilter::exact('posts.title', null, $addRelationConstraint));
```

## Scope filters

Sometimes more advanced filtering options are necessary. This is where scope filters and custom filters come in handy.

Scope filters allow you to add [local scopes](https://laravel.com/docs/5.6/eloquent#local-scopes) to your query by adding filters to the URL.

Consider the following scope on your model:

```php
public function scopeStartsBefore(Builder $query, $date): Builder
{
    return $query->where('starts_at', '<=', Carbon::parse($date));
}
```

To filter based on the `startsBefore` scope, add it to the `allowedFilters` array on the query builder:

```php
QueryBuilder::for(Event::class)
    ->allowedFilters([
        AllowedFilter::scope('starts_before'),
    ])
    ->get();
```

The following filter will now add the `startsBefore` scope to the underlying query:

```
GET /events?filter[starts_before]=2018-01-01
```

You can even pass multiple parameters to the scope by passing a comma separated list to the filter:

```
GET /events?filter[starts_between]=2018-01-01,2018-12-31
```

Scopes are usually not named with query filters in mind. Use [filter aliases](#filter-aliases) to alias them to something more appropriate:

```php
QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::scope('unconfirmed', 'whereHasUnconfirmedEmail'),
        // `?filter[unconfirmed]=1` will now add the `scopeWhereHasUnconfirmedEmail` to your query
    ]);
```

## Custom filters

You can specify custom filters using the `AllowedFilter::custom()` method. Custom filters are instances of invokable classes that implement the `\Spatie\QueryBuilder\Filters\Filter` interface. The `__invoke` method will receive the current query builder instance and the filter name/value. This way you can build any query your heart desires.

For example:

```php
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersUserPermission implements Filter
{
    public function __invoke(Builder $query, $value, string $property) : Builder
    {
        $query->whereHas('permissions', function (Builder $query) use ($value) {
            $query->where('name', $value);
        });
    }
}

// In your controller for the following request:
// GET /users?filter[permission]=createPosts

$users = QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::custom('permission', new FiltersUserPermission),
    ])
    ->get();

// $users will contain all users that have the `createPosts` permission
```

## Filter aliases

It can be useful to specify an alias for a filter to avoid exposing database column names. For example, your users table might have a `user_passport_full_name` column, which is a horrible name for a filter. Using aliases you can specify a new, shorter name for this filter:

```php
use Spatie\QueryBuilder\AllowedFilter;

// GET /users?filter[name]=John

$users = QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::exact('name', 'user_passport_full_name')) // will filter by the `user_passport_full_name` column
    ->get();
```

## Ignored filters values

You can specify a set of ignored values for every filter. This allows you to not apply a filter when these values are submitted.

```php
QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::exact('name')->ignore(null),
    ])
    ->get();
```

The `ignore()` method takes one or more values, where each may be an array of ignored values. Each of the following calls are valid:

* `ignore('should_be_ignored')`
* `ignore(null, '-1')`
* `ignore([null, 'ignore_me', 'also_ignored'])`

Given an array of values to filter for, only the subset of non-ignored values get passed to the filter. If all values are ignored, the filter does not get applied.

```php
// GET /user?filter[name]=forbidden,John%20Doe

QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::exact('name')->ignore('forbidden'),
    ])
    ->get();
// Returns only users where name matches 'John Doe'

// GET /user?filter[name]=ignored,ignored_too

QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::exact('name')->ignore(['ignored', 'ignored_too']),
    ])
    ->get();
// Filter does not get applied because all requested values are ignored.
```

## Default Filter Values

 You can specify a default value for a filter if a value for the filter was not present on the request. This is especially useful for boolean filters.
 
```php
QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::exact('name')->default('Joe'),
        AllowedFilter::scope('deleted')->default(false),
    ])
    ->get();
```
