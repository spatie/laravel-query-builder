---
title: Filtering
weight: 1
---

The `filter` query parameters can be used to filter results by partial property value, exact property value or if a property value exists in a given array of values. You can also specify custom filters for more advanced queries.

By default, no filters are allowed. All filters have to be specified using `allowedFilters()`. When trying to filter on properties that have not been allowed using `allowedFilters()` an `InvalidFilterQuery` exception will be thrown.

``` php
// GET /users?filter[name]=john&filter[email]=gmail
$users = QueryBuilder::for(User::class)
    ->allowedFilters('name', 'email')
    ->get();
// $users will contain all users with "john" in their name AND "gmail" in their email address
```

You can also pass in an array of filters to the `allowedFilters()` method.

``` php
// GET /users?filter[name]=john&filter[email]=gmail
$users = QueryBuilder::for(User::class)
    ->allowedFilters(['name', 'email'])
    ->get();
// $users will contain all users with "john" in their name AND "gmail" in their email address
```

You can specify multiple matching filter values by passing a comma separated list of values:

``` php
// GET /users?filter[name]=seb,freek
$users = QueryBuilder::for(User::class)
    ->allowedFilters('name')
    ->get();
// $users will contain all users that contain "seb" OR "freek" in their name
```
### Property Column Alias

It can be useful to expose properties for filtering, that do not share the exact naming of your database column. If you wanted to allow filtering on columns that may have a prefix in the database, you can use the following notation.

```php
use Spatie\QueryBuilder\Filter;

// GET /users?filter[name]=John
$users = QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('name', 'user_name')) // filter by the column 'user_name'
    ->get();
```


### Exact filters

When filtering models based on their IDs, a boolean value or a literal string, you'll want to use exact filters. This way `/users?filter[id]=1` won't match all users containing the digit `1` in their ID.

Exact filters can be added using `Spatie\QueryBuilder\Filter::exact('property_name')` in the `allowedFilters()` method.

``` php
use Spatie\QueryBuilder\Filter;

// GET /users?filter[name]=John%20Doe
$users = QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('name'))
    ->get();
// all users with the exact name "John Doe"
```

The query builder will automatically map `'true'` and `'false'` as booleans and a comma separated list of values as an array:
``` php
use Spatie\QueryBuilder\Filter;

// GET /users?filter[id]=1,2,3,4,5&filter[admin]=true
$users = QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('id'), Filter::exact('admin'))
    ->get();
// $users will contain all admin users with id 1, 2, 3, 4 or 5
```

### Scope filters

Sometimes you'll want to build more advanced filtering queries. This is where scope filters and custom filters come in handy.

Scope filters allow you to easily add [local scopes](https://laravel.com/docs/5.6/eloquent#local-scopes) to your query by adding filters to the URL.

Consider the following scope on your model:

```php
public function scopeStartsBefore(Builder $query, $date): Builder
{
    return $query->where('starts_at', '<=', Carbon::parse($date));
}
```

To filter based on the `startsBefore` scope simply add it to the `allowedFilters` on the query builder:

```php
QueryBuilder::for(Event::class)
    ->allowedFilters([
        Filter::scope('starts_before'),
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

### Custom filters

You can specify custom filters using the `Filter::custom()` method. Custom filters are simple, invokable classes that implement the `\Spatie\QueryBuilder\Filters\Filter` interface. This way you can create any query your heart desires.

For example:

``` php
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersUserPermission implements Filter
{
    public function __invoke(Builder $query, $value, string $property) : Builder
    {
        return $query->whereHas('permissions', function (Builder $query) use ($value) {
            $query->where('name', $value);
        });
    }
}

use Spatie\QueryBuilder\Filter;

// GET /users?filter[permission]=createPosts
$users = QueryBuilder::for(User::class)
    ->allowedFilters(Filter::custom('permission', new FiltersUserPermission))
    ->get();
// $users will contain all users that have the `createPosts` permission
```
### Ignored values for filters

You can specify a set of ignored values for every filter. This allows you to not apply a filter when these values are submitted.
```php
QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('name')->ignore(null))
    ->get();
```
The `ignore()` method takes one or more values, where each may be an array of ignored values. Each of the following calls are valid:
* `ignore('should_be_ignored')`  
* `ignore(null, '-1')`
* `ignore([null, 'ignore_me'],['also_ignored'])`

Given an array of values to filter for, only the subset of non-ignored values get passed to the filter. If all values are ignored, the filter does not get applied.

```php
// GET /user?filter[name]=forbidden,John Doe
QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('name')->ignore('forbidden'))
    ->get();

// Only users where name matches 'John Doe'

// GET /user?filter[name]=ignored,ignored_too
QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('name')->ignore(['ignored', 'ignored_too']))
    ->get();

// Filter does not get applied
```

## Default Filter Values

 You can specify a default value for a filter if a value for the filter was not present on the request.
```php
QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('name')->default('Joe'))
    ->get();
```
