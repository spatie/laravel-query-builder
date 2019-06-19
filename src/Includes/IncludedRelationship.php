<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;

class IncludedRelationship implements Includable
{
    public function __invoke(QueryBuilder $query, string $relationship)
    {
        $relatedTables = collect(explode('.', $relationship));

        $withs = $relatedTables
            ->mapWithKeys(function ($table, $key) use ($query, $relatedTables) {
                $fields = $query->getRequestedFieldsForRelatedTable(Str::snake($table));

                $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

                if (empty($fields)) {
                    return [$fullRelationName];
                }

                return [$fullRelationName => function ($query) use ($fields) {
                    $query->select($fields);
                }];
            })
            ->toArray();

        $query->with($withs);
    }
}
