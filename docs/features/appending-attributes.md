---
title: Appending attributes
weight: 5
---

Sometimes you will want to append some custom attributes into result from a Model. This can be done using the `append` parameter.

``` php
class User extends Model
{
    public function getFullnameAttribute()
    {
        return $this->firstname.' '.$this->lastname;
    }
}
```

``` php
// GET /users?append=fullname

$users = QueryBuilder::for(User::class)
    ->allowedAppends('fullname')
    ->get();
```

Of course you can pass a list of attributes to be appended.

```
// GET /users?append=fullname,ranking
```
