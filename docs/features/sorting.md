---
title: Sorting
weight: 2
---

The `sort` query parameter is used to determine by which property the results collection will be ordered. Sorting is ascending by default. Adding a hyphen (`-`) to the start of the property name will reverse the results collection.

```php
// GET /users?sort=-name
$users = QueryBuilder::for(User::class)->get();

// $users will be sorted by name and descending (Z -> A)
```

By default, all model properties can be used to sort the results. However, you can use the `allowedSorts` method to limit which properties are allowed to be used in the request.

When trying to sort by a property that's not specified in `allowedSorts()` an `InvalidSortQuery` exception will be thrown.

```php
// GET /users?sort=password
$users = QueryBuilder::for(User::class)
    ->allowedSorts('name')
    ->get();

// Will throw an `InvalidSortQuery` exception as `password` is not an allowed sorting property
```

To define a default sort parameter that should be applied without explicitly adding it to the request, you can use the `defaultSort` method.

```php
// GET /users
$users = QueryBuilder::for(User::class)
    ->defaultSort('name')
    ->allowedSorts('name', 'street')
    ->get();

// Will retrieve the users sorted by name
```

You can use `-` if you want to have the default order sorted descendingly.

```php
// GET /users
$users = QueryBuilder::for(User::class)
    ->defaultSort('-name')
    ->allowedSorts('name', 'street')
    ->get();

// Will retrieve the users sorted descendingly by name
```

You can also pass in an array of sorts to the `allowedSorts()` method.

```php
// GET /users?sort=name
$users = QueryBuilder::for(User::class)
    ->allowedSorts(['name', 'street'])
    ->get();

// Will retrieve the users sorted by name
```

You can sort by multiple properties by separating them with a comma:

```php
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

```php
 // GET /users?sort=-street
 $users = QueryBuilder::for(User::class)
    ->allowedSorts(Sort::field('street', 'actual_column_street')
    ->get();
 ```
