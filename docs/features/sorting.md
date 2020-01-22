---
title: Sorting
weight: 2
---

The `sort` query parameter is used to determine by which property the results collection will be ordered. Sorting is ascending by default and can be reversed by adding a hyphen (`-`) to the start of the property name.

All sorts have to be explicitly allowed by passing an array to the `allowedSorts()` method. The `allowedSorts` method takes an array of column names as strings or instances of `AllowedSorts`s.

For more advanced use cases, [custom sorts](#custom-sorts) can be used.

## Basic usage

```php
// GET /users?sort=-name

$users = QueryBuilder::for(User::class)
    ->allowedSorts('name')
    ->get();

// $users will be sorted by name and descending (Z -> A)
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

You can sort by multiple properties by separating them with a comma:

```php
// GET /users?sort=name,-street

$users = QueryBuilder::for(User::class)
    ->allowedSorts(['name', 'street'])
    ->get();

// $users will be sorted by name in ascending order with a secondary sort on street in descending order.
```

## Disallowed sorts

When trying to sort by a property that's not specified in `allowedSorts()` an `InvalidSortQuery` exception will be thrown.

```php
// GET /users?sort=password
$users = QueryBuilder::for(User::class)
    ->allowedSorts(['name'])
    ->get();

// Will throw an `InvalidSortQuery` exception as `password` is not an allowed sorting property
```

## Custom sorts

You can specify custom sorting methods using the `AllowedSort::custom()` method. Custom sorts are instances of invokable classes that implement the `\Spatie\QueryBuilder\Sorts\Sort` interface. The `__invoke` method will receive the current query builder instance, the direction to sort in and the sort's name. This way you can build any sorting query your heart desires.

For example sorting by string column length:

```php
class StringLengthSort implements \Spatie\QueryBuilder\Sorts\Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';

        $query->orderByRaw("LENGTH(`{$property}`) {$direction}");
    }
}
```

The custom `StringLengthSort` sort class can then be used like this to sort by the length of the `users.name` column:

```php
// GET /users?sort=name-length

$users = QueryBuilder::for(User::class)
    ->allowedSorts([
        AllowedSort::custom('name-length', new StringLengthSort(), 'name'),
    ])
    ->get();

// The requested `name-length` sort alias will invoke `StringLengthSort` with the `name` column name. 
```

To change the default direction of the a sort you can use `defaultDirection` :

```php
$customSort = AllowedSort::custom('custom-sort', new SentSort())->defaultDirection('desc');

$users = QueryBuilder::for(User::class)
            ->allowedSorts($customSort)
            ->defaultSort($customSort)->defaultDirection(SortDirection::DESCENDING)
            ->get();
```

## Using an alias for sorting

There may be occasions where it is not appropriate to expose the column name to the user.

Similar to using an alias when filtering, you can do this with for sorts as well.

The column name can be passed as optional parameter and defaults to the property string.

```php
 // GET /users?sort=-street
 $users = QueryBuilder::for(User::class)
    ->allowedSorts([
        AllowedSort::field('street', 'actual_column_street'),
    ])
    ->get();
 ```
