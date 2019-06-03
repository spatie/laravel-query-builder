# Easily build Eloquent queries from API requests

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)
[![Build Status](https://img.shields.io/circleci/project/github/spatie/laravel-query-builder/master.svg?style=flat-square)](https://circleci.com/gh/spatie/laravel-query-builder)
[![StyleCI](https://styleci.io/repos/117567334/shield?branch=master)](https://styleci.io/repos/117567334)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-query-builder.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-query-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)

This package allows you to filter, sort and include eloquent relations based on a request. The `QueryBuilder` used in this package extends Laravel's default Eloquent builder. This means all your favorite methods and macros are still available. Query parameter names follow the [JSON API specification](http://jsonapi.org/) as closely as possible.

## Basic usage

Filtering an API request: `/users?filter[name]=John`:

```php
use Spatie\QueryBuilder\QueryBuilder;

// ...

$users = QueryBuilder::for(User::class)
    ->allowedFilters('name')
    ->get();
// all `User`s that contain the string "John" in their name
```

Requesting relations from an API request: `/users?include=posts`:

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();
// all `User`s with their `posts` loaded
```

Works together nicely with existing queries:

```php
$query = User::where('active', true);

$user = QueryBuilder::for($query)
    ->allowedIncludes('posts', 'permissions')
    ->where('score', '>', 42) // chain on any of Laravel's query builder methods
    ->first();
```

Sorting an API request: `/users?sort=name`:

```php
$users = QueryBuilder::for(User::class)->get();
// all `User`s sorted by name
```

Have a look at the [usage section](#usage) below for advanced examples and features.

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-query-builder
```

You can optionally publish the config file with:
```bash
php artisan vendor:publish --provider="Spatie\QueryBuilder\QueryBuilderServiceProvider" --tag="config"
```

This is the contents of the published config file:
```php
return [

    /*
     * By default the package will use the `include`, `filter`, `sort` and `fields` query parameters.
     *
     * Here you can customize those names.
     */
    'parameters' => [
        'include' => 'include',

        'filter' => 'filter',

        'sort' => 'sort',

        'fields' => 'fields',
    ],

];
```

## Usage

### Including relationships

The `include` query parameter will load any Eloquent relation on the results collection.
By default, no includes are allowed. All includes must be specified using `allowedIncludes()`.

``` php
// GET /users?include=posts
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();

// $users will contain all users with their posts loaded
```

You can load multiple relationship by separating them with a comma:

``` php
// GET /users?include=posts,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts', 'permissions')
    ->get();

// $users will contain all users with their posts and permissions loaded
```

You can also pass in an array of includes to the `allowedIncludes()` method.

``` php
// GET /users?include=posts,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes(['posts', 'permissions'])
    ->get();

// $users will contain all users with their posts and permissions loaded
```

You can load nested relationships using `.`:

``` php
// GET /users?include=posts.comments,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts.comments', 'permissions')
    ->get();

// $users will contain all users with their posts, comments on their posts and permissions loaded
```

When trying to include relationships that have not been allowed using `allowedIncludes()` an `InvalidIncludeQuery` exception will be thrown.

Relation/include names will be converted to camelCase when looking for the corresponding relationship on the model. This means `/users?include=blog-posts` will try to load the `blogPosts()` relationship on the `User` model.

Once the relationships are loaded on the results collection you can include them in your response by using [Eloquent API resources and conditional relationships](https://laravel.com/docs/5.5/eloquent-resources#conditional-relationships).

### Filtering

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
#### Property Column Alias

It can be useful to expose properties for filtering, that do not share the exact naming of your database column. If you wanted to allow filtering on columns that may have a prefix in the database, you can use the following notation.

```php
use Spatie\QueryBuilder\Filter;

// GET /users?filter[name]=John
$users = QueryBuilder::for(User::class)
    ->allowedFilters(Filter::exact('name', 'user_name')) // filter by the column 'user_name'
    ->get();
```


#### Exact filters

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

#### Scope filters

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

#### Custom filters

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
    ->allowedFilters(Filter::custom('permission', FiltersUserPermission::class))
    ->get();
// $users will contain all users that have the `createPosts` permission
```
#### Ignored values for filters

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

### Sorting

The `sort` query parameter is used to determine by which property the results collection will be ordered. Sorting is ascending by default. Adding a hyphen (`-`) to the start of the property name will reverse the results collection.

``` php
// GET /users?sort=-name
$users = QueryBuilder::for(User::class)->get();

// $users will be sorted by name and descending (Z -> A)
```

By default, all model properties can be used to sort the results. However, you can use the `allowedSorts` method to limit which properties are allowed to be used in the request.

When trying to sort by a property that's not specified in `allowedSorts()` an `InvalidSortQuery` exception will be thrown.

``` php
// GET /users?sort=password
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name')
    ->get();

// Will throw an `InvalidSortQuery` exception as `password` is not an allowed sorting property
```

To define a default sort parameter that should be applied without explicitly adding it to the request, you can use the `defaultSort` method.

``` php
// GET /users
$users = QueryBuilder::for(User::class)
    ->defaultSort('name')
    ->allowedSorts('name', 'street')
    ->get();

// Will retrieve the users sorted by name
```

You can use `-` if you want to have the default order sorted descendingly.

``` php
// GET /users
$users = QueryBuilder::for(User::class)
    ->defaultSort('-name')
    ->allowedSorts('name', 'street')
    ->get();

// Will retrieve the users sorted descendingly by name
```

You can also pass in an array of sorts to the `allowedSorts()` method.

``` php
// GET /users?sort=name
$users = QueryBuilder::for(User::class)
    ->allowedSorts(['name', 'street'])
    ->get();

// Will retrieve the users sorted by name
```

You can sort by multiple properties by separating them with a comma:

``` php
// GET /users?sort=name,-street
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name', 'street')
    ->get();

// $users will be sorted by name in ascending order with a secondary sort on street in descending order.
```

#### Using an alias for sorting

There may be occasions where it is not appropriate to expose the column name to the user.

Similar to using [an alias when filtering](#property-column-alias) you can do this with for sorts as well.

The column name can be passed as optional parameter and defaults to the property string.

 ``` php
 // GET /users?sort=-street
 $users = QueryBuilder::for(User::class)
    ->allowedSorts(Sort::field('street', 'actual_column_street'))
    ->get();
 ```

### Selecting specific columns

Sometimes you'll want to fetch only a couple fields to reduce the overall size of your SQL query. This can be done using the `fields` query parameter. The following fetch only the users' `id` and `name`

```
GET /users?fields[users]=id,name
```

The SQL query will look like this:

```sql
SELECT "id", "name" FROM "users"
```

Using the `allowedFields` method you can limit which fields (columns) are allowed to be queried in the request.

When trying to select a column that's not specified in `allowedFields()` an `InvalidFieldQuery` exception will be thrown.

``` php
$users = QueryBuilder::for(User::class)
    ->allowedFields('name')
    ->get();

// GET /users?fields[users]=email will throw an `InvalidFieldQuery` exception as `email` is not an allowed field.
```

Selecting fields for included models works the same way. This is especially useful when including entire relationships when you only need a couple of columns. Consider the following example:

```
GET /posts?include=author&fields[author]=id,name
```

All posts will be fetched including only the name of the author. 

⚠️ Keep in mind that the fields query will completely override the `SELECT` part of the query. This means that you'll need to manually specify any columns required for relationships to work, in this case `id`. See issue #175 as well.

### Append attributes

Sometimes you will want to append some custom attributes into result from a Model. This can be done using the `append` parameter.

``` php
class User extends Model
{
    public function getFullnameAttribute()
    {
        return $this->firstname.' '.$this->lastname;
    }
}
```

``` php
// GET /users?append=fullname

$users = QueryBuilder::for(User::class)
    ->allowedAppends('fullname')
    ->get();
```

Of course you can pass a list of attributes to be appended.

```
// GET /users?append=fullname,ranking
```


### Other query methods

As the `QueryBuilder` extends Laravel's default Eloquent query builder you can use any method or macro you like. You can also specify a base query instead of the model FQCN:

``` php
QueryBuilder::for(User::where('id', 42)) // base query instead of model
    ->allowedIncludes('posts')
    ->where('activated', true) // chain on any of Laravel's query methods
    ->first(); // we only need one specific user
```

#### Pagination

This package doesn't provide any methods to help you paginate responses. However as documented above you can use Laravel's default [`paginate()` method](https://laravel.com/docs/5.5/pagination).

If you want to completely adhere to the JSON API specification you can also use our own [spatie/json-api-paginate](https://github.com/spatie/laravel-json-api-paginate)!

### Building fluent queries from the front end

If you're interested in building query urls on the front-end to match this package, you could use one of the below:

- [Vue](https://vuejs.org/): [vue-api-query package](https://github.com/robsontenorio/vue-api-query) by [Robson Tenório](https://github.com/robsontenorio).
- Javascript/[React](https://reactjs.org/): [cogent-js package](https://www.npmjs.com/package/cogent-js) by [Joel Male](https://github.com/joelwmale).

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Alex Vanderbist](https://github.com/AlexVanderbist)
- [All Contributors](../../contributors)

## Support us

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

Does your business depend on our contributions? Reach out and support us on [Patreon](https://www.patreon.com/spatie).
All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
