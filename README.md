# Easily build Eloquent queries from API requests

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)
[![Build Status](https://img.shields.io/travis/spatie/laravel-query-builder/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-query-builder)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-query-builder.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-query-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-query-builder.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-query-builder)

This package allows you to easily filter, sort and include eloquent relations based on the current request's query string. The `QueryBuilder` used in this package extends Laravel's default Eloquent builder so all your favorite methods and macro's are available.

## Example usage

Sorting an API request: `/users?sort=-name`:

```php
// $users will contain a all `User`s sorted by descending name
$users = QueryBuilder::for(User::class, request())->get();
```

Filtering an API request: `/users?filter[name]=John`:

```php
// $users will contain all `User`s that have "John" in their name
$users = QueryBuilder::for(User::class, request())
    ->allowedFilters('name')
    ->get();
```

Requesting relations from an API request: `/users?include=posts`:

```php
// $users will contain all `User`s with their `posts` loaded
$users = QueryBuilder::for(User::class, request())
    ->allowedIncludes('posts')
    ->get();
```

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-query-builder
```

## Usage

``` php
QueryBuilder::for(User::class, request())
    ->allowedFilters('name', Filter::exact('id'))
    ->allowedIncludes('posts')
    ->get();
```

### Testing

``` bash
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
