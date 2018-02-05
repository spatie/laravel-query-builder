# Changelog

All notable changes to `laravel-query-builder` will be documented in this file

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
