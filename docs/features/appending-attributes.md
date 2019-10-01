---
title: Appending attributes
weight: 5
---

When serializing a model to JSON, Laravel can add custom attributes using the [`append` method or `appends` property on the model](https://laravel.com/docs/master/eloquent-serialization#appending-values-to-json). The query builder also supports this using the `allowedAppends` method and `append` query parameter.

## Basic usage

Consider the following custom attribute accessor. It won't be included in the model's JSON by default:

```php
class User extends Model
{
    public function getFullnameAttribute()
    {
        return "{$this->firstname} {$this->lastname}";
    }
}
```

Using `allowedAppends` we can optionally include the given above append.

```php
// GET /users?append=fullname

$users = QueryBuilder::for(User::class)
    ->allowedAppends(['fullname'])
    ->get();
// Will call `$user->append('fullname')` on the query's results
```

Of course you can pass a list of attributes in the request to be appended:

```
// GET /users?append=fullname,ranking
```
