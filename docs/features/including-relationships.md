---
title: Including relationships
weight: 3
---

The `include` query parameter will load any Eloquent relation on the results collection.
By default, no includes are allowed. All includes must be specified using `allowedIncludes()`.

```php
// GET /users?include=posts
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();

// $users will contain all users with their posts loaded
```

You can load multiple relationship by separating them with a comma:

```php
// GET /users?include=posts,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts', 'permissions')
    ->get();

// $users will contain all users with their posts and permissions loaded
```

You can also pass in an array of includes to the `allowedIncludes()` method.

```php
// GET /users?include=posts,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes(['posts', 'permissions'])
    ->get();

// $users will contain all users with their posts and permissions loaded
```

You can load nested relationships using `.`:

```php
// GET /users?include=posts.comments,permissions
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts.comments', 'permissions')
    ->get();

// $users will contain all users with their posts, comments on their posts and permissions loaded
```

When trying to include relationships that have not been allowed using `allowedIncludes()` an `InvalidIncludeQuery` exception will be thrown.

Relation/include names will be converted to camelCase when looking for the corresponding relationship on the model. This means `/users?include=blog-posts` will try to load the `blogPosts()` relationship on the `User` model.

Once the relationships are loaded on the results collection you can include them in your response by using [Eloquent API resources and conditional relationships](https://laravel.com/docs/5.5/eloquent-resources#conditional-relationships).
