---
title: Selecting fields
weight: 4
---

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

Temp:

``` php
QueryBuilder::for(User::class)
    ->allowedFields('name', 'posts.id', 'posts.name')
    ->allowedIncludes('posts');
```

``` php
QueryBuilder::for(User::class)
    ->allowedFields('name')
    ->allowedIncludes(Include::make('posts')->allowedFields('id', 'name'));
```

``` php
QueryBuilder::for(User::class)
    ->allowedFields(UserResource::class) // implements `HasQueryBuilderFields` interface
    ->allowedIncludes('posts'); // checks User::query()->posts() relationship for the related model - uses the related model's allowed fields if it implements `HasQueryBuilderFields`
```
    
Selecting fields for included models works the same way. This is especially useful when including entire relationships when you only need a couple of columns. Consider the following example:

```
GET /posts?include=author&fields[author]=id,name
```

All posts will be fetched including only the name of the author. 

⚠️ Keep in mind that the fields query will completely override the `SELECT` part of the query. This means that you'll need to manually specify any columns required for relationships to work, in this case `id`. See issue #175 as well.
