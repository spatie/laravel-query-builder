---
name: laravel-query-builder
description: "Build filtered, sorted, and included API endpoints using spatie/laravel-query-builder. Activates when working with QueryBuilder, AllowedFilter, AllowedSort, AllowedInclude, or when the user mentions query parameters, API filtering, sorting, includes, or spatie/laravel-query-builder."
license: MIT
metadata:
  author: spatie
---

# Laravel Query Builder

## When to Apply

Activate this skill when:

- Building API endpoints that accept filter, sort, include, or fields query parameters
- Configuring allowed filters, sorts, includes, or field selections
- Creating custom filter, sort, or include classes
- Troubleshooting query builder exceptions or unexpected query results

## Basic Usage

```php
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;

// GET /users?filter[name]=John&sort=-created_at&include=posts
$users = QueryBuilder::for(User::class)
    ->allowedFilters('name', 'email')
    ->allowedSorts('name', 'created_at')
    ->allowedIncludes('posts', 'permissions')
    ->allowedFields('id', 'name', 'email')
    ->get();
```

## Filtering

### Filter Types

```php
use Spatie\QueryBuilder\AllowedFilter;

QueryBuilder::for(User::class)
    ->allowedFilters(
        AllowedFilter::partial('name'),           // WHERE name LIKE '%value%' (default)
        AllowedFilter::exact('email'),             // WHERE email = 'value'
        AllowedFilter::beginsWith('name'),         // WHERE name LIKE 'value%'
        AllowedFilter::endsWith('name'),           // WHERE name LIKE '%value'
        AllowedFilter::scope('active'),            // Calls scopeActive()
        AllowedFilter::callback('search', fn ($query, $value) => ...),
        AllowedFilter::exact('role')->default('user'),  // Default filter value
        AllowedFilter::exact('status')->nullable(),     // Allows null values
        AllowedFilter::exact('role')->ignore('admin'),  // Ignores specific values
        AllowedFilter::belongsTo('author'),             // Filter by BelongsTo relationship
        AllowedFilter::trashed(),                       // Filter soft deletes (with, only, without)
    );
```

### Operator Filters

```php
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Enums\FilterOperator;

QueryBuilder::for(User::class)
    ->allowedFilters(
        AllowedFilter::operator('salary', FilterOperator::GreaterThan),
        AllowedFilter::operator('age', FilterOperator::LessThanOrEqual),
        AllowedFilter::operator('salary', FilterOperator::Dynamic), // Operator from request
    );

// GET /users?filter[salary]=gt:50000 (with Dynamic operator)
```

### Relation Filters

Filter by related model properties using dot notation:

```php
// GET /users?filter[posts.title]=Laravel
QueryBuilder::for(User::class)
    ->allowedFilters(AllowedFilter::partial('posts.title'));
```

### Custom Column Names

```php
// GET /users?filter[email]=john
// Queries the 'user_email' column
AllowedFilter::exact('email', 'user_email');
```

### Custom Filters

Implement `Spatie\QueryBuilder\Filters\Filter`:

```php
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FiltersUserPermission implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereHas('permissions', fn ($q) => $q->where('name', $value));
    }
}

AllowedFilter::custom('permission', new FiltersUserPermission());
```

## Sorting

```php
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Enums\SortDirection;

QueryBuilder::for(User::class)
    ->allowedSorts(
        'name',
        'created_at',
        AllowedSort::field('order', 'sort_order'),   // Alias: ?sort=order queries sort_order
        AllowedSort::custom('popular', new SortMostPopular()),
        AllowedSort::callback('random', fn ($query, $descending) => $query->inRandomOrder()),
    )
    ->defaultSort('name')
    ->defaultSorts('name', AllowedSort::field('date', 'created_at')->defaultDirection(SortDirection::Descending));

// GET /users?sort=-created_at,name (descending created_at, ascending name)
```

### Custom Sorts

Implement `Spatie\QueryBuilder\Sorts\Sort`:

```php
use Spatie\QueryBuilder\Sorts\Sort;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Enums\SortDirection;

class SortMostPopular implements Sort
{
    public function __invoke(Builder $query, SortDirection $direction, string $property): void
    {
        $query->withCount('followers')->orderBy('followers_count', $direction->value);
    }
}
```

## Including Relationships

```php
use Spatie\QueryBuilder\AllowedInclude;

QueryBuilder::for(User::class)
    ->allowedIncludes(
        'posts',                                     // Eager loads posts (also allows postsCount and postsExists)
        'posts.comments',                            // Nested eager loading
        AllowedInclude::count('commentsCount'),      // Only withCount, no full relation
        AllowedInclude::exists('postsExists'),       // Only withExists
        AllowedInclude::relationship('profile', 'userProfile'), // Alias
        AllowedInclude::callback('latestPost', fn ($query) => $query->latestOfMany()),
    );

// GET /users?include=posts,commentsCount,postsExists
```

### Aggregate Includes

```php
AllowedInclude::min('postsViewsMin', 'posts', 'views');   // withMin('posts', 'views')
AllowedInclude::max('postsViewsMax', 'posts', 'views');   // withMax('posts', 'views')
AllowedInclude::sum('postsViewsSum', 'posts', 'views');   // withSum('posts', 'views')
AllowedInclude::avg('postsViewsAvg', 'posts', 'views');   // withAvg('posts', 'views')

// GET /users?include=postsViewsSum,postsViewsAvg
```

### Custom Includes

Implement `Spatie\QueryBuilder\Includes\IncludeInterface`:

```php
use Spatie\QueryBuilder\Includes\IncludeInterface;
use Illuminate\Database\Eloquent\Builder;

class IncludeLatestPost implements IncludeInterface
{
    public function __invoke(Builder $query, string $include): void
    {
        $query->with(['latestPost' => fn ($q) => $q->latest()]);
    }
}

AllowedInclude::custom('latestPost', new IncludeLatestPost());
```

## Field Selection

```php
// GET /users?fields[users]=id,name&fields[posts]=id,title&include=posts
QueryBuilder::for(User::class)
    ->allowedFields('id', 'name', 'email')
    ->allowedIncludes('posts')
    ->get();
```

## Configuration

Published to `config/query-builder.php`:

```php
return [
    // Custom query parameter names
    'parameters' => [
        'include' => 'include',
        'filter' => 'filter',
        'sort' => 'sort',
        'fields' => 'fields',
    ],

    // Array value delimiter
    'delimiter' => ',',

    // Include suffixes (for count, exists, and aggregate includes)
    'suffixes' => [
        'count' => 'Count',
        'exists' => 'Exists',
        'min' => 'Min',
        'max' => 'Max',
        'sum' => 'Sum',
        'avg' => 'Avg',
    ],

    // Disable exception throwing for invalid queries
    'disable_invalid_filter_query_exception' => false,
    'disable_invalid_sort_query_exception' => false,
    'disable_invalid_include_query_exception' => false,
];
```

## Wildcard Allow-All

Allow any requested filter, sort, or include without explicit listing. Restricted to `local` and `testing` environments:

```php
QueryBuilder::for(User::class)
    ->allowedFilters('*')
    ->allowedSorts('*')
    ->allowedIncludes('*');
```

## Starting from Existing Queries

```php
// From an Eloquent query
QueryBuilder::for(User::where('active', true))
    ->allowedFilters('name')
    ->get();

// From a relation
QueryBuilder::for($team->users())
    ->allowedFilters('name')
    ->get();
```

## Common Patterns

### Controller with Full Query Builder

```php
class UsersController
{
    public function index()
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('email'),
                AllowedFilter::scope('active'),
            )
            ->allowedSorts('name', 'created_at')
            ->allowedIncludes('posts', 'permissions')
            ->allowedFields('id', 'name', 'email')
            ->defaultSort('name')
            ->paginate();

        return UserResource::collection($users);
    }
}
```

### Dynamic Arrays with Variadic Methods

```php
$filters = ['name', 'email'];
$sorts = ['name', 'created_at'];

QueryBuilder::for(User::class)
    ->allowedFilters(...$filters)
    ->allowedSorts(...$sorts);
```

## Common Pitfalls

- **N+1 queries**: Always use `allowedIncludes()` to eager load relationships instead of accessing them in views/resources without loading
- **Forgetting to allow**: All filters, sorts, includes, and fields must be explicitly allowed. Unallowed parameters throw exceptions by default
- **Filter value types**: Filter values come from query strings as strings. Use `AllowedFilter::exact()` for boolean/integer columns, or handle casting in custom filters
- **Nested include counts**: Count and exists variants are only auto-generated for top-level includes, not for nested includes like `posts.comments`
- **Field selection with includes**: When using `allowedFields()` with `allowedIncludes()`, make sure to include the foreign key columns needed for the relationships
