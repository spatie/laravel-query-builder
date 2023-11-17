<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IncludedRelationship implements IncludeInterface
{
    /** @var Closure|null */
    public $getRequestedFieldsForRelatedTable;

    public function __invoke(Builder $query, string $relationship)
    {
        $relatedTables = collect(explode('.', $relationship));

        $withs = $relatedTables
            ->mapWithKeys(function ($table, $key) use ($relatedTables) {
                $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

                if ($this->getRequestedFieldsForRelatedTable) {
                    $fields = ($this->getRequestedFieldsForRelatedTable)($fullRelationName);
                }

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

    public static function getIndividualRelationshipPathsFromInclude(string $include): Collection
    {
        return collect(explode('.', $include))
            ->reduce(function (Collection $includes, string $relationship) {
                if ($includes->isEmpty()) {
                    return $includes->push($relationship);
                }

                return $includes->push("{$includes->last()}.{$relationship}");
            }, collect());
    }
}
