# Build Eloquent queries from API requests

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)
[![Build Status](https://img.shields.io/circleci/project/github/spatie/laravel-query-builder/master.svg?style=flat-square)](https://circleci.com/gh/spatie/laravel-query-builder)
[![StyleCI](https://styleci.io/repos/117567334/shield?branch=master)](https://styleci.io/repos/117567334)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-query-builder.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-query-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)

This package allows you to filter, sort and include eloquent relations based on a request. The `QueryBuilder` used in this package extends Laravel's default Eloquent builder. This means all your favorite methods and macros are still available. Query parameter names follow the [JSON API specification](http://jsonapi.org/) as closely as possible.

## Basic usage

### Filter a query based on a request: `/users?filter[name]=John`:

```php
use Spatie\QueryBuilder\QueryBuilder;

$users = QueryBuilder::for(User::class)
    ->allowedFilters('name')
    ->get();

// all `User`s that contain the string "John" in their name
```

[Read more about filtering features like: partial filters, exact filters, scope filters, custom filters, ignored values, default filter values, ...](https://docs.spatie.be/laravel-query-builder/v2/features/filtering/)

### Including relations based on a request: `/users?include=posts`:

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();

// all `User`s with their `posts` loaded
```

[Read more about include features like: including nested relationships, including relationship count, ...](https://docs.spatie.be/laravel-query-builder/v2/features/including-relationships/)

### Sorting a query based on a request: `/users?sort=id`:

```php
$users = QueryBuilder::for(User::class)
    ->allowedSorts('id')
    ->get();

// all `User`s sorted by ascending id
```

[Read more about sorting features like: custom sorts, sort direction, ...](https://docs.spatie.be/laravel-query-builder/v2/features/sorting/)

### Works together nicely with existing queries:

```php
$query = User::where('active', true);

$userQuery = QueryBuilder::for($query) // start from an existing Builder instance
    ->withTrashed() // use your existing scopes
    ->allowedIncludes('posts', 'permissions')
    ->where('score', '>', 42); // chain on any of Laravel's query builder methods
```

### Selecting fields for a query: `/users?fields=id,email`

```php
$users = QueryBuilder::for(User::class)
    ->allowedFields(['id', 'email'])
    ->get();

// the fetched `User`s will only have their id & email set
```

[Read more about selecting fields.](https://docs.spatie.be/laravel-query-builder/v2/features/selecting-fields/)

### Appending attributes to a query: `/users?append=full_name`

```php
$users = QueryBuilder::for(User::class)
    ->allowedAppends('full_name')
    ->get()
    ->toJson();

// the resulting JSON will have the `getFullNameAttribute` attributes included
```

[Read more about appending attributes.](https://docs.spatie.be/laravel-query-builder/v2/features/appending-attributes/)

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-query-builder
```

Read the installation notes on the docs site: [https://docs.spatie.be/laravel-query-builder/v2/installation-setup](https://docs.spatie.be/laravel-query-builder/v2/installation-setup/).

## Documentation

You can find the documentation on [https://docs.spatie.be/laravel-query-builder/v2](https://docs.spatie.be/laravel-query-builder/v2).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving the media library? Feel free to [create an issue on GitHub](https://github.com/spatie/laravel-query-builder/issues), we'll try to address it as soon as possible.

If you've found a bug regarding security please mail [freek@spatie.be](mailto:freek@spatie.be) instead of using the issue tracker.

### Upgrading from v1 to v2

Please see [UPGRADING.md](UPGRADING.md) for details.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Alex Vanderbist](https://github.com/AlexVanderbist)
- [All Contributors](../../contributors)

## Support us

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

Does your business depend on our contributions? Reach out and support us on [Patreon](https://www.patreon.com/spatie).
All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

