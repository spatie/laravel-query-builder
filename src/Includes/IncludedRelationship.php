<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Exception;
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
            ->mapWithKeys(function ($table, $key) use ($relatedTables, $query) {
                $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

                if ($this->getRequestedFieldsForRelatedTable) {

                    $tableName = null;
                    $strategy = config('query-builder.convert_relation_table_name_strategy', false);

                    if ($strategy !== false) {
                        // Try to resolve the related model's table name
                        try {
                            // Use the current query's model to resolve the relationship
                            $relatedModel = $query->getModel()->{$fullRelationName}()->getRelated();
                            $tableName = $relatedModel->getTable();
                        } catch (Exception $e) {
                            // If we can not figure out the table don't do anything
                            $tableName = null;
                        }
                    }

                    $fields = ($this->getRequestedFieldsForRelatedTable)($fullRelationName, $tableName);
                }

                if (empty($fields)) {
                    return [$fullRelationName];
                }

                return [$fullRelationName => function ($query) use ($fields) {
                    $query->select($query->qualifyColumns($fields));
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
