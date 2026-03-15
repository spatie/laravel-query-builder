<?php

namespace Spatie\QueryBuilder\Includes;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IncludedRelationship implements IncludeInterface
{
    private ?Closure $fieldsCallback = null;

    public function setFieldsCallback(Closure $callback): void
    {
        $this->fieldsCallback = $callback;
    }

    public function __invoke(Builder $query, string $relationship): void
    {
        $relatedTables = collect(explode('.', $relationship));

        $withs = $relatedTables
            ->mapWithKeys(function ($table, $key) use ($relatedTables, $query) {
                $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');
                $fields = [];

                if ($this->fieldsCallback) {
                    $tableName = null;
                    $strategy = config('query-builder.convert_relation_table_name_strategy');

                    if ($strategy !== null) {
                        try {
                            $relatedModel = $query->getModel()->{$fullRelationName}()->getRelated();
                            $tableName = $relatedModel->getTable();
                        } catch (Exception) {
                            $tableName = null;
                        }
                    }

                    $fields = ($this->fieldsCallback)($fullRelationName, $tableName);
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
