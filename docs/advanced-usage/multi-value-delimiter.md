---
title: Multi value delimiter
weight: 4
---

Sometimes values to filter for could include commas. You can change the delimiter used to split array values by setting the `delimiter` key in the `query-builder` config file.

```php
// config/query-builder.php

return [
    'delimiter' => '|',
];
```

With this configuration, a request like `GET /api/endpoint?filter[voltage]=12,4V|4,7V|2,1V` would be parsed as:

```php
// filters: [ 'voltage' => [ '12,4V', '4,7V', '2,1V' ]]
```

__Note that this applies to ALL values for filters, includes and sorts.__
