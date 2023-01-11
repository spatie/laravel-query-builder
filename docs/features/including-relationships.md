---
title: Including relationships
weight: 3
---

The `include` query parameter will load any Eloquent relation or relation count on the resulting models.
All includes must be explicitly allowed using `allowedIncludes()`. This method takes an array of relationship names or `AllowedInclude` instances.

## Basic usage

```php
// GET /users?include=posts

$users = QueryBuilder::for(User::class)
    ->allowedIncludes(['posts'])
    ->get();

// $users will have all their their `posts()` related models loaded
```

You can load multiple relationships by separating them with a comma:

```php
// GET /users?include=posts,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes(['posts', 'permissions'])
    ->get();

// $users will contain all users with their posts and permissions loaded
```

## Default includes

There is no way to include relationships by default in this package. Default relationships are built-in to Laravel itself using the `with()` method on a query:

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes(['friends'])
    ->with('posts') // posts will always by included, friends can be requested
    ->withCount('posts')
    ->get();
```

## Disallowed includes

When trying to include relationships that have not been allowed using `allowedIncludes()` an `InvalidIncludeQuery` exception will be thrown. Its exception message contains the allowed includes for reference.

## Nested relationships

You can load nested relationships using the dot `.` notation:

```php
// GET /users?include=posts.comments,permissions

$users = QueryBuilder::for(User::class)
    ->allowedIncludes(['posts.comments', 'permissions'])
    ->get();

// $users will contain all users with their posts, comments on their posts and permissions loaded
```

## Including related model count

Every allowed include will automatically allow requesting its related model count using a `Count` suffix. On top of that it's also possible to specifically allow requesting and querying the related model count (and not include the entire relationship).

Under the hood this uses Laravel's `withCount method`. [Read more about the `withCount` method here](https://laravel.com/docs/master/eloquent-relationships#counting-related-models).

```php
// GET /users?include=postsCount,friendsCount

$users = QueryBuilder::for(User::class)
    ->allowedIncludes([
        'posts', // allows including `posts` or `postsCount`
        AllowedInclude::count('friendsCount'), // only allows include the number of `friends()` related models
    ]); 
// every user in $users will contain a `posts_count` and `friends_count` property
```

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
    ->allowedIncludes([
        AllowedInclude::custom('comments_sum_votes', new AggregateInclude('votes', 'sum'), 'comments'),
    ])
    ->get();

// every post in $posts will contain a `comments_sum_votes` property
```

## Selecting included fields

You can select only some fields to be included using the [`allowedFields` method on the query builder](https://spatie.be/docs/laravel-query-builder/v5/features/selecting-fields/).

⚠️ `allowedFields` must be called before `allowedIncludes`. Otherwise the query builder wont know what fields to include for the requested includes and an exception will be thrown.

## Include casing

Relation/include names will be passed from request URL to the query directly. This means `/users?include=blog-posts` will try to load `blog-posts` relationship and  `/users?include=blogPosts` will try to load the `blogPosts()` relationship.

## Eloquent API resources

Once the relationships are included, we'd recommend including them in your response by using [Eloquent API resources and conditional relationships](https://laravel.com/docs/master/eloquent-resources#conditional-relationships).
