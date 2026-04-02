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

## Disable filter splitting globally

If you need filter values to stay intact globally, you can disable filter delimiter splitting in config:

```php
// config/query-builder.php

'filter_value_splitting_enabled' => false,
```

This only affects filter values. Includes, sorts, fields, and appends will continue using the configured global delimiter. The default value is `true`.

## Per filter delimiter

You can override the delimiter for a specific filter using the `delimiter()` method. This is useful when a filter value may contain the default delimiter character.

```php
// GET /api/endpoint?filter[voltage]=12,4V|4,7V|2,1V&filter[name]=John,Jane

QueryBuilder::for(Model::class)
    ->allowedFilters(
        AllowedFilter::exact('voltage')->delimiter('|'),
        AllowedFilter::exact('name'), // still uses the default comma delimiter
    )
    ->get();
```

Per-filter delimiters still take precedence when filter delimiter splitting has been disabled globally.

To disable splitting entirely for a filter, set the delimiter to an empty string:

```php
AllowedFilter::exact('external_id')->delimiter('')
```
