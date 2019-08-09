---
title: Extending query builder
weight: 1
---

As the `QueryBuilder` extends Laravel's default Eloquent query builder you can use any method or macro you like. You can also specify a base query instead of the model FQCN:

```php
QueryBuilder::for(User::where('id', 42)) // base query instead of model
    ->allowedIncludes(['posts'])
    ->where('activated', true) // chain on any of Laravel's query methods
    ->first(); // we only need one specific user
```
