---
title: Selecting fields
weight: 4
---

Sometimes you'll want to fetch only a couple fields to reduce the overall size of your SQL query. This can be done by specifying some fields using the `allowedFields` method and using the `fields` request query parameter. 

## Basic usage

The following example fetches only the users' `id` and `name`:

```
GET /users?fields[users]=id,name

$users = QueryBuilder::for(User::class)
    ->allowedFields(['id', 'name'])
    ->toSql();
```

The SQL query will look like this:

```sql
SELECT "id", "name" FROM "users"
```

When not allowing any fields explicitly, Eloquent's default behaviour of selecting all fields will be used. 

## Disallowed fields/selects

When trying to select a column that's not specified in `allowedFields()` an `InvalidFieldQuery` exception will be thrown:

```php
$users = QueryBuilder::for(User::class)
    ->allowedFields('name')
    ->get();

// GET /users?fields[users]=email will throw an `InvalidFieldQuery` exception as `email` is not an allowed field.
```

## Selecting fields for included relations

Selecting fields for included models works the same way. This is especially useful when you only need a couple of columns from an included relationship. Consider the following example:

```php
GET /posts?include=author&fields[author]=id,name

QueryBuilder::for(Post::class)
    ->allowedFields('author.id', 'author.name')
    ->allowedIncludes('author');

// All posts will be fetched including _only_ the name of the author. 
```

⚠️ Keep in mind that the fields query will completely override the `SELECT` part of the query. This means that you'll need to manually specify any columns required for Eloquent relationships to work, in the above example `author.id`. See issue #175 as well.

⚠️ `allowedFields` must be called before `allowedIncludes`. Otherwise the query builder won't know what fields to include for the requested includes and an exception will be thrown.

