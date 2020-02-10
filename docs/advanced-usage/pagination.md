---
title: Pagination
weight: 2
---

This package doesn't provide any methods to help you paginate responses. However as documented above you can use Laravel's default [`paginate()` method](https://laravel.com/docs/5.5/pagination).

If you want to completely adhere to the JSON API specification you can also use our own [spatie/json-api-paginate](https://github.com/spatie/laravel-json-api-paginate)!

## Adding Parameters to Pagination

By default the query parameters wont be added to the pagination json. You can append the request query to the pagination json by using the `appends` method available on the [LengthAwarePaginator](https://laravel.com/api/6.x/Illuminate/Contracts/Pagination/LengthAwarePaginator.html#method_appends).

```php
$users = QueryBuilder::for(User::class)
    ->allowedFilters(['name', 'email'])
    ->paginate()
    ->appends(request()->query());
```
