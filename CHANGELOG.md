# Changelog

All notable changes to `laravel-query-builder` will be documented in this file

## 5.1.1 - 2022-12-02

### What's Changed

- Fix `array_diff_assoc` BC break in v5.1.0 by @stevebauman in https://github.com/spatie/laravel-query-builder/pull/827

### New Contributors

- @stevebauman made their first contribution in https://github.com/spatie/laravel-query-builder/pull/827

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/5.1.0...5.1.1

## 4.0.4 - 2022-11-28

### What's Changed

- bugfix: appending to `pluck`ed values (that are not a `Model`) is not possible
- Add version number to installation command in V4 by @jamesbhatta in https://github.com/spatie/laravel-query-builder/pull/786

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/4.0.3...4.0.4

## 5.1.0 - 2022-11-28

### What's Changed

- Feature: Update Generics  in IncludeInterface by @sidigi in https://github.com/spatie/laravel-query-builder/pull/810
- Feature: Add PHP 8.2 Support by @patinthehat in https://github.com/spatie/laravel-query-builder/pull/825
- Feature: Add `beginsWithStrict` filter by @danilopinotti in https://github.com/spatie/laravel-query-builder/pull/821
- Bugfix: ignore allowed filters by @davidjr82 in https://github.com/spatie/laravel-query-builder/pull/818
- Bugfix: Change self to static when creating query builder by @olliescase in https://github.com/spatie/laravel-query-builder/pull/819
- Docs: Update filtering.md by @Dion213 in https://github.com/spatie/laravel-query-builder/pull/801
- Misc: Add Dependabot Automation by @patinthehat in https://github.com/spatie/laravel-query-builder/pull/823
- Misc: Bump actions/checkout from 2 to 3 by @dependabot in https://github.com/spatie/laravel-query-builder/pull/824

### New Contributors

- @Dion213 made their first contribution in https://github.com/spatie/laravel-query-builder/pull/801
- @sidigi made their first contribution in https://github.com/spatie/laravel-query-builder/pull/810
- @patinthehat made their first contribution in https://github.com/spatie/laravel-query-builder/pull/823
- @dependabot made their first contribution in https://github.com/spatie/laravel-query-builder/pull/824
- @davidjr82 made their first contribution in https://github.com/spatie/laravel-query-builder/pull/818
- @olliescase made their first contribution in https://github.com/spatie/laravel-query-builder/pull/819
- @danilopinotti made their first contribution in https://github.com/spatie/laravel-query-builder/pull/821

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/5.0.3...5.1.0

## 5.0.3 - 2022-07-29

### What's Changed

- Fixed: Some links in the documentation for v5 pointing to v2 pages by @hameedraha in https://github.com/spatie/laravel-query-builder/pull/757
- static return type when returning $this by @lorenzolosa in https://github.com/spatie/laravel-query-builder/pull/775
- [PHP 8.2] Fix `${var}` string interpolation deprecation by @Ayesh in https://github.com/spatie/laravel-query-builder/pull/779
- Fix grammar by @clouder in https://github.com/spatie/laravel-query-builder/pull/784
- Add Inertia.js Tables for Laravel Query Builder by @fabianpnke in https://github.com/spatie/laravel-query-builder/pull/790
- Fix Laravel 9 PHPStan generic check for  `__invoke()` method of Filter by @kayw-geek in https://github.com/spatie/laravel-query-builder/pull/781
- Fix for Warning by @shaunluedeke in https://github.com/spatie/laravel-query-builder/pull/791

### New Contributors

- @hameedraha made their first contribution in https://github.com/spatie/laravel-query-builder/pull/757
- @lorenzolosa made their first contribution in https://github.com/spatie/laravel-query-builder/pull/775
- @Ayesh made their first contribution in https://github.com/spatie/laravel-query-builder/pull/779
- @clouder made their first contribution in https://github.com/spatie/laravel-query-builder/pull/784
- @fabianpnke made their first contribution in https://github.com/spatie/laravel-query-builder/pull/790
- @shaunluedeke made their first contribution in https://github.com/spatie/laravel-query-builder/pull/791

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/5.0.2...5.0.3

## 5.0.2 - 2022-04-25

## What's Changed

- Fix Laravel 9 PHPStan generic check  by @kayw-geek in https://github.com/spatie/laravel-query-builder/pull/749

## New Contributors

- @kayw-geek made their first contribution in https://github.com/spatie/laravel-query-builder/pull/749

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/5.0.1...5.0.2

## 4.0.3 - 2022-03-23

## What's Changed

- V4 - Add support for laravel > 7.30.4 by @luilliarcec in https://github.com/spatie/laravel-query-builder/pull/744

## New Contributors

- @luilliarcec made their first contribution in https://github.com/spatie/laravel-query-builder/pull/744

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/4.0.2...4.0.3

## 5.0.1 - 2022-03-18

## What's Changed

- Update README.md by @wayz9 in https://github.com/spatie/laravel-query-builder/pull/713
- Fix release order in CHANGELOG by @medvinator in https://github.com/spatie/laravel-query-builder/pull/717
- Fix include casing docs by @canvural in https://github.com/spatie/laravel-query-builder/pull/733
- Adapt documentation for publishing package config by @dominikb in https://github.com/spatie/laravel-query-builder/pull/734
- Fix warning from passing null to explode for includeParts by @steven-fox in https://github.com/spatie/laravel-query-builder/pull/742

## New Contributors

- @wayz9 made their first contribution in https://github.com/spatie/laravel-query-builder/pull/713
- @medvinator made their first contribution in https://github.com/spatie/laravel-query-builder/pull/717
- @canvural made their first contribution in https://github.com/spatie/laravel-query-builder/pull/733
- @steven-fox made their first contribution in https://github.com/spatie/laravel-query-builder/pull/742

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/5.0.0...5.0.1

## 5.0.0 - 2022-01-13

- add support for Laravel 9
- drop support for older versions

## 4.0.2 - 2021-12-26

## What's Changed

- DOC: New sample with multiple default sorts by @williamxsp in https://github.com/spatie/laravel-query-builder/pull/694
- PHP 8.1 Support by @Medalink in https://github.com/spatie/laravel-query-builder/pull/702

## New Contributors

- @williamxsp made their first contribution in https://github.com/spatie/laravel-query-builder/pull/694
- @Medalink made their first contribution in https://github.com/spatie/laravel-query-builder/pull/702

**Full Changelog**: https://github.com/spatie/laravel-query-builder/compare/4.0.1...4.0.2

## 4.0.1 - 2021-10-27

- revert deferred service provider (#677)

## 4.0.0 - 2021-10-20

- nested filters will no longer be automatically camel-cased to match a relationship name
- includes will no longer be automatically camel-cased to match a relationship name
- fields will no longer be automatically snake-cased to match table or column names
- switch to deferred service provider

Take a look at the [upgrade guide](./UPGRADING.md) for a more detailed explanation.

## 3.6.0 - 2021-09-06

- add callback sorts (#654)

## 3.5.0 - 2021-07-05

- add support for cursor pagination

## 3.4.3 - 2021-07-05

- fix unexpected lowercase appends (#637)

## 3.4.2 - 2021-07-05

- no changes

## 3.4.1 - 2021-05-24

- fix simple paginator append not working (#633)

## 3.4.0 - 2021-05-20

- add support for custom includes (#623)
- add support for getting request data from the request body (#589)
- fix issues when cloning `QueryBuilder` (#621)

## 3.3.4 - 2020-11-26

- prepend table name to `WHERE` clause for ambiguous partial filters (#567)
- add PHP 8 support

## 3.3.3 - 2020-10-27

- prepend table name to `WHERE` clause for ambiguous exact filters (#467)

## 3.3.2 - 2020-10-27

- fix config key to disable `InvalidFilterQuery` exception

## 3.3.1 - 2020-10-11

- make nested scope compatible with older Laravel (#542)

## 3.3.0 - 2020-10-05

- add ability to filter by nested relationship scopes (#519)
- add config key to disable `InvalidFilterQuery` exception (#525)

## 3.2.4 - 2020-10-01

- update what defines an ignored filter value (#533)

## 3.2.3 - 2020-09-30

- add LengthAwarePaginator to QueryBuilder (#532)

## 3.2.2 - 2020-09-09

- Revert changes from v3.2.1 to `AllowedFilter::filter()`

## 3.2.1 - 2020-09-09

- Fix filtering associative arrays (#488)
- AllowedFilter::filter() takes a `Illuminate\Database\Eloquent\Builder` instead of a QueryBuilder instance

## 3.2.0 - 2020-09-08

- add support for Laravel 8

## 3.1.0 - 2020-08-18

- add individual array delimiters for includes, filters, appends and sorts
- ensure relations queried using the exact filter are actual relations on the model

## 3.0.0 - 2020-08-18

New major version. Please read the [UPGRADING](UPGRADING.md) guide *before* upgrading.

- `Spatie\QueryBuilder\QueryBuilder` class no longer extends Laravel's `Illuminate\Database\Eloquent\Builder`

## 2.8.2 - 2020-05-25

- fix scope filters that are added via macros (e.g. `onlyTrashed`) (#469)

## 2.8.1 - 2020-03-20

- make service provider deferrable (#381)

## 2.8.0 - 2020-03-02

- add support for Laravel 7

## 2.7.2 - 2020-02-26

- small fix for lumen (#436)

## 2.7.1 - 2020-02-26

- small fix for lumen in service provider

## 2.7.0 - 2020-02-12

- add support for model binding in scope filter parameters (#415)

## 2.6.1 - 2020-02-11

- fix alias for multiple allowed includes (#414)

## 2.6.0 - 2020-02-10

- add `FiltersTrashed` for filtering soft-deleted models
- add `FiltersCallback` for filtering using a callback

## 2.5.1 - 2020-01-22

- fix dealing with empty or `null` includes (#395)
- fix passing an associative array of scope filter values (#387)

## 2.5.0 - 2020-01-09

- add `defaultDirection`

## 2.4.0 - 2020-01-04

- add support for a custom filter delimiter (#369)

## 2.3.0 - 2019-10-08

- resolve `QueryBuilderRequest` from service container

## 2.2.1 - 2019-10-03

- fix issue when passing camel-cased includes (#336)

## 2.2.0 - 2019-09-24

- add option to disable parsing relationship constraints when filtering related model properties in the exact and partial filters (#262)
- fix selecting fields from included relationships that are multiple levels deep (#317)

## 2.1.0 - 2019-09-03

- add support for Laravel 6

## 2.0.1 - 2019-08-12

- update doc block for `QueryBuilder::for()`
- add missing typehint in `SortsField`

## 2.0.0 - 2019-08-12

- removed request macros
- sorts and field selects are not allowed by default and need to be explicitly allowed
- requesting an include suffixed with `Count` will add the related models' count using `$query->withCount()`
- custom sorts and filters now need to be passed as instances
- renamed `Spatie\QueryBuilder\Sort` to `Spatie\QueryBuilder\AllowedSort`
- renamed `Spatie\QueryBuilder\Included` to `Spatie\QueryBuilder\AllowedInclude`
- renamed `Spatie\QueryBuilder\Filter` to `Spatie\QueryBuilder\AllowedFilter`
- `Filter`, `Include` and `Sort` interfaces no longer need to return the `Builder` instance
- `allowedFields` should be called before `allowedIncludes`
- filters can now have default values
- includes will be converted to camelcase before being parsed

## 1.17.5 - 2019-07-08

- bugfix: correctly parse sorts in `chunk`ed query (#299)
- bugfix: don't parse empty values in arrays for partial filters (#285)

## 1.17.4 - 2019-06-03

- bugfix: `orderByRaw` is no longer being rejected as a sorting option (#258)
- bugfix: `addSelect` is no longer being replaced by the `?fields` parameter (#260)
- bugfix: take leading dash into account when remembering generated sort columns (#272)
- bugfix: `allowedIncludes` no longer adds duplicate includes for nested includes (#251)

## 1.17.3 - 2019-04-16

- bugfix: remove duplicate parsing of (default) sort clauses

## 1.17.2 - 2019-04-12

- bugfix: replace missing `sort()` method on `QueryBuilderRequest`
- bugfix: don't escape `allowedSort`s and their aliases
- bugfix: don't escape `allowedField`s

## 1.17.1 - 2019-04-09

- security fixes

## 1.16.1 - 2019-04-09

- security fixes

## 1.17.0 - 2019-03-11

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- moved features to traits
- started using `QueryBuilderRequest` to read data from the current request
- deprecated request macros (`Request::filters()`, `Request::includes()`, etc...)
- raised minimum supported Laravel version to 5.6.34

## 1.16.0 - 2019-03-05

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for multiple default sorts (#214)

## 1.15.2 - 2019-02-28

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for Laravel 5.5 and up (again)
- add support for PHP 7.1 and up (again)

## 1.15.1 - 2019-02-28

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix default sort not parsing correctly (#178)

## 1.15.0 - 2019-02-27

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- drop support for Laravel 5.7 and lower
- drop support for PHP 7.1 and lower

## 1.14.0 - 2019-02-27

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add aliased sorts (#164)

## 1.13.2 - 2019-02-27

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for Laravel 5.8
- use Str:: and Arr:: instead of helper methods

## 1.13.1 - 2019-01-18

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix detection of false-positives for ignored values (#154)
- fix broken morphTo includes (#130)

## 1.13.0 - 2019-01-12

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- allow ignoring specific filter values using `$filter->ignore()`
- allow filtering related model attributes `allowedFilters('related-model.name')`
- fix for filtering by relation model properties
- add custom sort classes

## 1.12.0 - 2018-11-27

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- allow differently named columns

## 1.11.2 - 2018-10-30

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix exception when using filters with nested arrays (#117)
- fix overwritten fields when using `allowedIncludes` with many-to-many relationships (#118)

## 1.11.1 - 2018-10-09

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix exception when using `allowedFields()` but selecting none

## 1.11.0 - 2018-10-03

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add `allowedFields` method
- fix & cleanup `Request::fields()` macro
- fix fields option (`SELECT * FROM table` instead of `SELECT table.* FROM table`)

## 1.10.4 - 2018-10-02

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix parsing empty filters from url

## 1.10.3 - 2018-09-17

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- improve compatibility with Lumen

## 1.10.2 - 2018-08-28

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for Laravel 5.7
- add framework/laravel as a dependency

## 1.10.1 - 2018-08-21

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- improve compatibility with Lumen by only publishing the config file in console mode

## 1.10.0 - 2018-06-12

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for instantiated custom filter classes

## 1.9.6 - 2018-06-11

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix for using reserved SQL words as attributes in Postgres

## 1.9.5 - 2018-06-09

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- make sure filtering on string with special characters just works

## 1.9.4 - 2018-06-06

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix for using reserved SQL words as attributes

## 1.9.3 - 2018-06-05

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- resolved #14

## 1.9.2 - 2018-05-21

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- prevent double sorting statments

## 1.9.1 - 2018-05-15

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- improvements around field selection

## 1.9.0 - 2018-05-02

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add `Filter::scope()` for querying scopes
- explicitly defining parent includes in nested queries is no longer required

## 1.8.0 - 2018-03-28

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add `allowedAppends()`

## 1.7.0 - 2018-03-23

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add ability to customize query parameter names

## 1.6.0 - 2018-03-05

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for selecting specific columns using `?fields[table]=field_name`

## 1.5.3 - 2018-02-09

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- allow arrays in filters

## 1.5.2 - 2018-02-08

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for Laravel 5.6

## 1.5.1 - 2018-02-07

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- fix: initializing scopes, macro's, the onDelete callback and eager loads from base query on QueryBuilder

## 1.5.0 - 2018-02-06

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- use specific exceptions for every invalid query

## 1.4.0 - 2018-02-05

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- allow multiple sorts

## 1.3.0 - 2018-02-05

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- allow `allowedIncludes`, `allowedFilters` and `allowedSorts` to accept arrays

## 1.2.1 - 2018-02-03

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- remove auto registering facade from composer.json

## 1.2.0 - 2018-01-29

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add support for global scopes and soft deletes

## 1.1.2 - 2018-01-23

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- bugfix: revert #11 (escaping `_` and `%` in LIKE queries)

## 1.1.1 - 2018-01-22

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- escape `_` and `%` in LIKE queries

## 1.1.0 - 2018-01-20

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- add ability to set a default sort attribute

## 1.0.1 - 2018-01-19

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- bugfix: using `allowedSorts` together with an empty sort query parameter no longer throws an exception

## 1.0.0 - 2018-01-17

**DO NOT USE: THIS VERSION ALLOWS SQL INJECTION ATTACKS**

- initial release! 🎉
