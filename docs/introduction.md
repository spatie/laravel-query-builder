---
title: Introduction
weight: 1
---

This package allows you to filter, sort and include eloquent relations based on a request. The `QueryBuilder` used in this package extends Laravel's default Eloquent builder. This means all your favorite methods and macros are still available. Query parameter names follow the [JSON API specification](http://jsonapi.org/) as closely as possible.

## Basic usage

Filtering an API request: `/users?filter[name]=John`:

```php
use Spatie\QueryBuilder\QueryBuilder;

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

Have a look at the basic usage section on the left for advanced examples and features.

## We have badges!

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)
[![Build Status](https://img.shields.io/circleci/project/github/spatie/laravel-query-builder/master.svg?style=flat-square)](https://circleci.com/gh/spatie/laravel-query-builder)
[![StyleCI](https://styleci.io/repos/117567334/shield?branch=master)](https://styleci.io/repos/117567334)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-query-builder.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-query-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)

![https://imgflip.com/i/36x6d6](Look at all those badges)
