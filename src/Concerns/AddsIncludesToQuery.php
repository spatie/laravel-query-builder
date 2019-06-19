<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Included;

trait AddsIncludesToQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedIncludes;

    public function allowedIncludes($includes): self
    {
        $includes = is_array($includes) ? $includes : func_get_args();

        $this->allowedIncludes = collect($includes)
            ->flatMap(function (string $include) {
                return $this->getIndividualRelationshipPathsFromInclude($include);
            })
            ->map(function (string $include) {
                return Included::relationship($include);
            });

        $this->guardAgainstUnknownIncludes();

        $this->addRequestedIncludesToQuery();

        return $this;
    }

    protected function addRequestedIncludesToQuery()
    {
        $this->request->includes()
            ->reject(function (string $include) {
                return Str::endsWith($include, config('query-builder.count_suffix'));
            })
            ->pipe(function (Collection $includes) {
                $this->addIncludedWithsToQuery($includes);
            });

        $this->request->includes()
            ->filter(function (string $include) {
                return Str::endsWith($include, config('query-builder.count_suffix'));
            })
            ->pipe(function (Collection $withCounts) {
                $this->addIncludedWithCountsToQuery($withCounts);
            });
    }

    protected function guardAgainstUnknownIncludes()
    {
        $includes = $this->request->includes();

        $diff = $includes->diff($this->allowedIncludes->map->getName());

        if ($diff->count()) {
            throw InvalidIncludeQuery::includesNotAllowed($diff, $this->allowedIncludes);
        }

        // TODO: Check for non-existing relationships?
    }

    protected function getIndividualRelationshipPathsFromInclude(string $include)
    {
        return collect(explode('.', $include))
            ->reduce(function ($includes, $relationship) {
                if ($includes->isEmpty()) {
                    return $includes->push($relationship);
                }

                return $includes->push("{$includes->last()}.{$relationship}");
            }, collect());
    }

    protected function addIncludedWithsToQuery(Collection $includes)
    {
        $includes
            ->flatMap(function (string $include) {
                $relatedTables = collect(explode('.', $include));

                return $relatedTables
                    ->mapWithKeys(function ($table, $key) use ($relatedTables) {
                        $fields = $this->getRequestedFieldsForRelatedTable(Str::snake($table));

                        $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

                        if (empty($fields)) {
                            return [$fullRelationName];
                        }

                        return [$fullRelationName => function ($query) use ($fields) {
                            $query->select($fields);
                        }];
                    });
            })
            ->pipe(function (Collection $withs) {
                $this->with($withs->all());
            });
    }

    protected function addIncludedWithCountsToQuery(Collection $includes)
    {
        $counts = $includes->map(function (string $include) {
            return Str::before($include, config('query-builder.count_suffix'));
        });

        $this->withCount($counts->toArray());
    }
}
