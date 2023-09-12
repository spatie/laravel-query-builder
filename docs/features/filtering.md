---
title: Filtering
weight: 1
---

The `filter` query parameters can be used to add `where` clauses to your Eloquent query. Out of the box we support filtering results by partial attribute value, exact attribute value or even if an attribute value exists in a given array of values. For anything more advanced, custom filters can be used.

By default, all filters have to be explicitly allowed using `allowedFilters()`. This method takes an array of strings or `AllowedFilter` instances. An allowed filter can be partial, beginsWithStrict, endsWithStrict, exact, scope or custom. By default, any string values passed to `allowedFilters()` will automatically be converted to `AllowedFilter::partial()` filters.

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

By passing column name strings to `allowedFilters`, **partial** filters are automatically applied.

## Disallowed filters

Finally, when trying to filter on properties that have not been allowed using `allowedFilters()` an `InvalidFilterQuery` exception will be thrown along with a list of allowed filters.


## Disable InvalidFilterQuery exception

You can set in configuration file to not throw an InvalidFilterQuery exception when a filter is not set in allowedFilter method. This does **not** allow using any filter, it just disables the exception.

```php
'disable_invalid_filter_query_exception' => true
```

By default the option is set false.

## Partial, beginsWithStrict and endsWithStrict filters

By default, all values passed to `allowedFilters` are converted to partial filters. The underlying query will be modified to use a `LIKE LOWER(%value%)` statement. Because this can cause missed indexes, it's often worth considering a `beginsWithStrict` filter for the beginning of the value, or an `endsWithStrict` filter for the end of the value. These filters will use a `LIKE value%` statement and a `LIKE %value` statement respectively, instead of the default partial filter. This can help optimize query performance and index utilization.

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

The query builder will automatically map `1`, `0`, `'true'`, and `'false'` as boolean values and a comma separated list of values as an array:

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

Sometimes more advanced filtering options are necessary. This is where scope filters, callback filters and custom filters come in handy.

Scope filters allow you to add [local scopes](https://laravel.com/docs/master/eloquent#local-scopes) to your query by adding filters to the URL. This works for scopes on the queried model or its relationships using dot-notation.

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

You can even pass multiple parameters to the scope by passing a comma separated list to the filter and use dot-notation for querying scopes on a relationship:

```
GET /events?filter[schedule.starts_between]=2018-01-01,2018-12-31
```

When using scopes that require model instances in the parameters, we'll automatically try to inject the model instances into your scope. This works the same way as route model binding does for injecting Eloquent models into controllers. For example:

```php
public function scopeEvent(Builder $query, \App\Models\Event $event): Builder
{
    return $query->where('id', $event->id);
}

// GET /events?filter[event]=1 - the event with ID 1 will automatically be resolved and passed to the scoped filter
```

Scopes are usually not named with query filters in mind. Use [filter aliases](#filter-aliases) to alias them to something more appropriate:

```php
QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::scope('unconfirmed', 'whereHasUnconfirmedEmail'),
        // `?filter[unconfirmed]=1` will now add the `scopeWhereHasUnconfirmedEmail` to your query
    ]);
```

## Trashed filters

When using Laravel's [soft delete feature](https://laravel.com/docs/master/eloquent#querying-soft-deleted-models) you can use the `AllowedFilter::trashed()` filter to query these models. 

The `FiltersTrashed` filter responds to particular values:

- `with`: include soft-deleted records to the result set
- `only`: return only 'trashed' records at the result set
- any other value: return only records without that are not soft-deleted in the result set

For example:

```php
QueryBuilder::for(Booking::class)
    ->allowedFilters([
        AllowedFilter::trashed(),
    ]);

// GET /bookings?filter[trashed]=only will only return soft deleted models
```

## Callback filters

If you want to define a tiny custom filter, you can use a callback filter. Using `AllowedFilter::callback(string $name, callable $filter)` you can specify a callable that will be executed when the filter is requested. 

The filter callback will receive the following parameters: `Builder $query, mixed $value, string $name`. You can modify the `Builder` object to add your own query constraints.

For example:

```php
QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::callback('has_posts', function (Builder $query, $value) {
            $query->whereHas('posts');
        }),
    ]);
```

Using PHP 7.4 this example becomes a lot shorter:

```php
QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::callback('has_posts', fn (Builder $query) => $query->whereHas('posts')),
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
    public function __invoke(Builder $query, $value, string $property)
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
        AllowedFilter::scope('permission')->default(null),
    ])
    ->get();
```

## Nullable Filter

You can mark a filter nullable if you want to retrieve entries whose filtered value is null. This way you can apply the filter with an empty value, as shown in the example.

```php
// GET /user?filter[name]=&filter[permission]=

QueryBuilder::for(User::class)
    ->allowedFilters([
        AllowedFilter::exact('name')->nullable(),
        AllowedFilter::scope('permission')->nullable(),
    ])
    ->get();
```

