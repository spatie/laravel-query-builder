# Changelog

All notable changes to `laravel-query-builder` will be documented in this file

## 1.9.6 - 2018-06-11

- fix for using reserved SQL words as attributes in Postgres

## 1.9.5 - 2018-06-09

- make sure filtering on string with special characters just works

## 1.9.4 - 2018-06-06

- fix for using reserved SQL words as attributes

## 1.9.3 - 2018-06-05

- resolved #14

## 1.9.2 - 2018-05-21

- prevent double sorting statments

## 1.9.1 - 2018-05-15

- improvements around field selection

## 1.9.0 - 2018-05-02

- add `Filter::scope()` for querying scopes
- explicitly defining parent includes in nested queries is no longer required

## 1.8.0 - 2018-03-28

- add `allowedAppends()`

## 1.7.0 - 2018-03-23

- add ability to customize query parameter names

## 1.6.0 - 2018-03-05

- add support for selecting specific columns using `?fields[table]=field_name`

## 1.5.3 - 2018-02-09

- allow arrays in filters

## 1.5.2 - 2018-02-08

- add support for Laravel 5.6

## 1.5.1 - 2018-02-07

- fix: initializing scopes, macro's, the onDelete callback and eager loads from base query on QueryBuilder

## 1.5.0 - 2018-02-06

- use specific exceptions for every invalid query

## 1.4.0 - 2018-02-05

- allow multiple sorts

## 1.3.0 - 2018-02-05

- allow `allowedIncludes`, `allowedFilters` and `allowedSorts` to accept arrays

## 1.2.1 - 2018-02-03

- remove auto registering facade from composer.json

## 1.2.0 - 2018-01-29

- add support for global scopes and soft deletes

## 1.1.2 - 2018-01-23

- bugfix: revert #11 (escaping `_` and `%` in LIKE queries)

## 1.1.1 - 2018-01-22

- escape `_` and `%` in LIKE queries

## 1.1.0 - 2018-01-20

- add ability to set a default sort attribute

## 1.0.1 - 2018-01-19

- bugfix: using `allowedSorts` together with an empty sort query parameter no longer throws an exception

## 1.0.0 - 2018-01-17

- initial release! 🎉
