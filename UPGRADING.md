# Upgrading

Because there are many breaking changes an upgrade is not that easy. There are many edge cases this guide does not cover. We accept PRs to improve this guide.

## From v1 to v2

There are a lot of renamed methods and classes in this release. An advanced IDE like PhpStorm is recommended to rename these methods and classes in your code base. Use the refactor -> rename functionality instead of find & replace.

- rename `Spatie\QueryBuilder\Sort` to `Spatie\QueryBuilder\AllowedSort`
- rename `Spatie\QueryBuilder\Included` to `Spatie\QueryBuilder\AllowedInclude`
- rename `Spatie\QueryBuilder\Filter` to `Spatie\QueryBuilder\AllowedFilter`
- replace request macro's like `request()->filters()`, `request()->includes()`, etc... with their related methods on the `QueryBuilderRequest` class. This class needs to be instantiated with a request object, (more info here: https://github.com/spatie/laravel-query-builder/issues/328):
    * `request()->includes()` -> `QueryBuilderRequest::fromRequest($request)->includes()`
    * `request()->filters()` -> `QueryBuilderRequest::fromRequest($request)->filters()`
    * `request()->sorts()` -> `QueryBuilderRequest::fromRequest($request)->sorts()`
    * `request()->fields()` -> `QueryBuilderRequest::fromRequest($request)->fields()`
    * `request()->appends()` -> `QueryBuilderRequest::fromRequest($request)->appends()`
- please note that the above methods on `QueryBuilderRequest` do not take any arguments. You can use the `contains` to check for a certain filter/include/sort/...
- make sure the second argument for `AllowedSort::custom()` is an instance of a sort class, not a classname
    * `AllowedSort::custom('name', MySort::class)` -> `AllowedSort::custom('name', new MySort())`
- make sure the second argument for `AllowedFilter::custom()` is an instance of a filter class, not a classname
    * `AllowedFilter::custom('name', MyFilter::class)` -> `AllowedFilter::custom('name', new MyFilter())`
- make sure all required sorts are allowed using `allowedSorts()`
- make sure all required field selects are allowed using `allowedFields()`
- make sure `allowedFields()` is always called before `allowedIncludes()`
