---
title: Introduction
weight: 1
---

This package allows you to filter, sort and include eloquent relations based on a request. The `QueryBuilder` used in this package extends Laravel's default Eloquent builder. This means all your favorite methods and macros are still available. Query parameter names follow the [JSON API specification](http://jsonapi.org/) as closely as possible.

## Basic usage

### Filtering an API request: `/users?filter[name]=John`:

```php
use Spatie\QueryBuilder\QueryBuilder;

$users = QueryBuilder::for(User::class)
    ->allowedFilters('name')
    ->get();
// all `User`s that contain the string "John" in their name
```

[Read more about filtering features like: partial filters, exact filters, scope filters, custom filters, ignored values, default filter values, ...]()

### Requesting relations from an API request: `/users?include=posts`:

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();
// all `User`s with their `posts` loaded
```

[Read more about include features like: custom includes, including nested relationships, including relationship count, ...]()

### Sorting an API request based on user ID's: `/users?sort=id`:

```php
$users = QueryBuilder::for(User::class)
    ->allowedSorts('id')
    ->get();
// all `User`s sorted by ascending id
```

[Read more about sorting features like: custom sorts, sort direction, ...]()

### Works together nicely with existing queries:

```php
$query = User::where('active', true);

$user = QueryBuilder::for($query)
    ->allowedIncludes('posts', 'permissions')
    ->where('score', '>', 42) // chain on any of Laravel's query builder methods
    ->first();
```

### Selecting fields for a query: `/users?fields=id,email`

```php
$users = QueryBuilder::for(User::class)
    ->allowedFields(['id', 'email'])
    ->get();
// the fetched `User`s will only have their id & email set
```

### Appending attributes to a query: `/users?append=full_name`

```php
$users = QueryBuilder::for(User::class)
    ->allowedAppends('full_name')
    ->get()
    ->toJson();
// all `User`s will have the `getFullNameAttribute` accessor appended to them
```

Have a look at the basic usage section on the left for more examples and features.

## We have badges!

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)
[![Build Status](https://img.shields.io/circleci/project/github/spatie/laravel-query-builder/master.svg?style=flat-square)](https://circleci.com/gh/spatie/laravel-query-builder)
[![StyleCI](https://styleci.io/repos/117567334/shield?branch=master)](https://styleci.io/repos/117567334)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-query-builder.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-query-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)

![Look at all those badges](https://i.imgflip.com/36x6d6.jpg)
