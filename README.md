
[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

# Build Eloquent queries from API requests

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)
![Test Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-query-builder/run-tests.yml?label=tests&branch=main)
![Code Style Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-query-builder/php-cs-fixer.yml?label=code%20style&branch=main)
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

[Read more about filtering features like: partial filters, exact filters, scope filters, custom filters, ignored values, default filter values, ...](https://spatie.be/docs/laravel-query-builder/v5/features/filtering/)

### Including relations based on a request: `/users?include=posts`:

```php
$users = QueryBuilder::for(User::class)
    ->allowedIncludes('posts')
    ->get();

// all `User`s with their `posts` loaded
```

[Read more about include features like: including nested relationships, including relationship count, custom includes, ...](https://spatie.be/docs/laravel-query-builder/v5/features/including-relationships/)

### Sorting a query based on a request: `/users?sort=id`:

```php
$users = QueryBuilder::for(User::class)
    ->allowedSorts('id')
    ->get();

// all `User`s sorted by ascending id
```

[Read more about sorting features like: custom sorts, sort direction, ...](https://spatie.be/docs/laravel-query-builder/v5/features/sorting/)

### Works together nicely with existing queries:

```php
$query = User::where('active', true);

$userQuery = QueryBuilder::for($query) // start from an existing Builder instance
    ->withTrashed() // use your existing scopes
    ->allowedIncludes('posts', 'permissions')
    ->where('score', '>', 42); // chain on any of Laravel's query builder methods
```

### Selecting fields for a query: `/users?fields[users]=id,email`

```php
$users = QueryBuilder::for(User::class)
    ->allowedFields(['id', 'email'])
    ->get();

// the fetched `User`s will only have their id & email set
```

[Read more about selecting fields.](https://spatie.be/docs/laravel-query-builder/v5/features/selecting-fields/)

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-query-builder.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-query-builder)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-query-builder
```

Read the installation notes on the docs site: [https://spatie.be/docs/laravel-query-builder/v5/installation-setup](https://spatie.be/docs/laravel-query-builder/v5/installation-setup/).

## Documentation

You can find the documentation on [https://spatie.be/docs/laravel-query-builder/v5](https://spatie.be/docs/laravel-query-builder/v5).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving the media library? Feel free to [create an issue on GitHub](https://github.com/spatie/laravel-query-builder/issues), we'll try to address it as soon as possible.

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

### Upgrading

Please see [UPGRADING.md](UPGRADING.md) for details.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

### Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

- [Alex Vanderbist](https://github.com/AlexVanderbist)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
