---
title: Multi value delimiter
weight: 4
---

Sometimes values to filter for could include commas. This is why you can specify the delimiter symbol using the `QueryBuilderRequest` to overwrite the default behaviour.

```php
// GET /api/endpoint?filter=12,4V|4,7V|2,1V

QueryBuilderRequest::setArrayValueDelimiter('|');

QueryBuilder::for(Model::class)
    ->allowedFilters(AllowedFilter::exact('voltage'))
    ->get();

// filters: [ 'voltage' => [ '12,4V', '4,7V', '2,1V' ]]
```

__Note that this applies to ALL values for filters, includes and sorts__

## Usage 

There are multiple opportunities where the delimiter can be set.

You can define it in a `ServiceProvider` to apply it globally, or define a middleware that can be applied only on certain `Controllers`.
```php
// YourServiceProvider.php
public function boot() {
    QueryBuilderRequest::setArrayDelimiter(';');
}

// ApplySemicolonDelimiterMiddleware.php
public function handle($request, $next) {
    QueryBuilderRequest::setArrayDelimiter(';');
    return $next($request);
}
```
