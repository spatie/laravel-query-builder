---
title: Including relationships
weight: 3
---

The `include` query parameter will load any Eloquent relation or relation count on the resulting models.
All includes must be explicitly allowed using `allowedIncludes()`. This method takes relationship names or `AllowedInclude` instances as arguments.

## Basic usage

```php
// GET /users?include=posts

$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();

// $users will have all their `posts()` related models loaded
```

You can load multiple relationships by separating them with a comma:

```php
// GET /users?include=posts,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts', 'permissions')
    ->get();

// $users will contain all users with their posts and permissions loaded
```

## Default includes

There is no way to include relationships by default in this package. Default relationships are built-in to Laravel itself using the `with()` method on a query:

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('friends')
    ->with('posts') // posts will always by included, friends can be requested
    ->withCount('posts')
    ->withExists('posts')
    ->get();
```

## Disallowed includes

When trying to include relationships that have not been allowed using `allowedIncludes()` an `InvalidIncludeQuery` exception will be thrown. Its exception message contains the allowed includes for reference.

## Nested relationships

You can load nested relationships using the dot `.` notation:

```php
// GET /users?include=posts.comments,permissions

$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts.comments', 'permissions')
    ->get();

// $users will contain all users with their posts, comments on their posts and permissions loaded
```

## Including related model count

Every allowed include will automatically allow requesting its related model count using a `Count` suffix. On top of that it's also possible to specifically allow requesting and querying the related model count (and not include the entire relationship).

Under the hood this uses Laravel's `withCount method`. [Read more about the `withCount` method here](https://laravel.com/docs/master/eloquent-relationships#counting-related-models).

```php
// GET /users?include=postsCount,friendsCount

$users = QueryBuilder::for(User::class)
    ->allowedIncludes(
        'posts', // allows including `posts` or `postsCount` or `postsExists`
        AllowedInclude::count('friendsCount'), // only allows include the number of `friends()` related models
    );
// every user in $users will contain a `posts_count` and `friends_count` property
```

## Including related model exists

Every allowed include will automatically allow requesting its related model exists using a `Exists` suffix. On top of that it's also possible to specifically allow requesting and querying the related model exists (and not include the entire relationship).

Under the hood this uses Laravel's `withExists method`. [Read more about the `withExists` method here](https://laravel.com/docs/master/eloquent-relationships#other-aggregate-functions).

```php
// GET /users?include=postsExists,friendsExists

$users = QueryBuilder::for(User::class)
    ->allowedIncludes(
        'posts', // allows including `posts` or `postsCount` or `postsExists`
        AllowedInclude::exists('friendsExists'), // only allows include the existence of `friends()` related models
    );
// every user in $users will contain a `posts_exists` and `friends_exists` property
```

## Including aggregate values (min, max, sum, avg)

You can include aggregate values for related models using `AllowedInclude::min()`, `AllowedInclude::max()`, `AllowedInclude::sum()`, and `AllowedInclude::avg()`. These correspond to Laravel's `withMin()`, `withMax()`, `withSum()`, and `withAvg()` methods.

Unlike `count` and `exists` includes, aggregate includes require you to specify both the relationship name and the column to aggregate. This means they cannot be auto-generated from strings and must be defined explicitly.

```php
// GET /users?include=postsViewsSum,postsViewsAvg

$users = QueryBuilder::for(User::class)
    ->allowedIncludes(
        AllowedInclude::sum('postsViewsSum', 'posts', 'views'),
        AllowedInclude::avg('postsViewsAvg', 'posts', 'views'),
    )
    ->get();

// every user in $users will contain a `posts_sum_views` and `posts_avg_views` property
```

All four aggregate types work the same way:

```php
AllowedInclude::min('postsViewsMin', 'posts', 'views');  // adds withMin('posts', 'views')
AllowedInclude::max('postsViewsMax', 'posts', 'views');  // adds withMax('posts', 'views')
AllowedInclude::sum('postsViewsSum', 'posts', 'views');  // adds withSum('posts', 'views')
AllowedInclude::avg('postsViewsAvg', 'posts', 'views');  // adds withAvg('posts', 'views')
```

The resulting attribute names follow Laravel's convention: `{relation}_{function}_{column}` (e.g. `posts_sum_views`).

The suffixes used for matching include names can be customized in the `query-builder` config file using the `min_suffix`, `max_suffix`, `sum_suffix`, and `avg_suffix` keys.

## Include aliases

It can be useful to specify an alias for an include to enable friendly relationship names. For example, your users table might have a `userProfile` relationship, which might be neater just specified as `profile`. Using aliases you can specify a new, shorter name for this include:

```php
use Spatie\QueryBuilder\AllowedInclude;

// GET /users?include=profile

$users = QueryBuilder::for(User::class)
    ->allowedIncludes(AllowedInclude::relationship('profile', 'userProfile')) // will include the `userProfile` relationship
    ->get();
```

## Custom includes

You can specify custom includes using the `AllowedInclude::custom()` method. Custom includes are instances of invokable classes that implement the `\Spatie\QueryBuilder\Includes\IncludeInterface` interface. The `__invoke` method will receive the current query builder instance and the include name. This way you can build any query your heart desires.

For example:

```php
use Spatie\QueryBuilder\Includes\IncludeInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Post;

class AggregateInclude implements IncludeInterface
{
    protected string $column;

    protected string $function;

    public function __construct(string $column, string $function)
    {
        $this->column = $column;

        $this->function = $function;
    }

    public function __invoke(Builder $query, string $relations)
    {
        $query->withAggregate($relations, $this->column, $this->function);
    }
}

// In your controller for the following request:
// GET /posts?include=comments_sum_votes

$posts = QueryBuilder::for(Post::class)
    ->allowedIncludes(
        AllowedInclude::custom('comments_sum_votes', new AggregateInclude('votes', 'sum'), 'comments'),
    )
    ->get();

// every post in $posts will contain a `comments_sum_votes` property
```

## Callback includes

If you want to define a tiny custom include, you can use a callback include. Using `AllowedInclude::callback(string $name, Closure $callback, ?string $internalName = null)` you can specify a Closure that will be executed when the includes is requested. 

You can modify the `Builder` object to add your own query constraints.

For example:

```php
QueryBuilder::for(User::class)
    ->allowedIncludes(
        AllowedInclude::callback('latest_post', function (Builder $query) {
            $query->latestOfMany();
        }),
    );
```

## Selecting included fields

You can select only some fields to be included using the [`allowedFields` method on the query builder](https://spatie.be/docs/laravel-query-builder/v6/features/selecting-fields/).

## Include casing

Relation/include names will be passed from request URL to the query directly. This means `/users?include=blog-posts` will try to load `blog-posts` relationship and  `/users?include=blogPosts` will try to load the `blogPosts()` relationship.

## Allowing all includes

If you want to allow any include that is present in the request without explicitly listing them, you can pass `'*'` as the argument to `allowedIncludes()`. This will skip validation and dynamically allow all requested includes using `AllowedInclude::relationship()`, automatically generating count and exists variants for non nested includes.

```php
// GET /users?include=posts,permissions

$users = QueryBuilder::for(User::class)
    ->allowedIncludes('*')
    ->get();

// All includes from the request will be loaded
```

**Security warning:** Using the wildcard allows loading any relationship, which can expose sensitive data or cause performance issues. For this reason, the wildcard is only allowed in `local` and `testing` environments. A `WildcardNotAllowedInEnvironment` exception will be thrown in any other environment.

## Eloquent API resources

Once the relationships are included, we'd recommend including them in your response by using [Eloquent API resources and conditional relationships](https://laravel.com/docs/master/eloquent-resources#conditional-relationships).
