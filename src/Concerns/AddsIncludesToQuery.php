<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\Includes\Includable;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;

trait AddsIncludesToQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedIncludes;

    public function allowedIncludes($includes): self
    {
        $includes = is_array($includes) ? $includes : func_get_args();

        $this->allowedIncludes = collect($includes)
            ->flatMap(function ($include): Collection {
                if ($include instanceof Includable) {
                    return collect([$include]);
                }

                if (Str::endsWith($include, config('query-builder.count_suffix'))) {
                    return AllowedInclude::count($include);
                }

                return AllowedInclude::relationship($include);
            });

        $this->guardAgainstUnknownIncludes();

        $this->addIncludesToQuery($this->request->includes());

        return $this;
    }

    protected function addIncludesToQuery(Collection $includes)
    {
        $includes->each(function ($include) {
            $include = $this->findInclude($include);

            $include->include($this);
        });
    }

    protected function findInclude(string $include): ?AllowedInclude
    {
        return $this->allowedIncludes
            ->first(function (AllowedInclude $included) use ($include) {
                return $included->isForInclude($include);
            });
    }

    protected function guardAgainstUnknownIncludes()
    {
        // TODO: fix this mess

        $includes = $this->request->includes();

        $allowedIncludeNames = $this->allowedIncludes->map->getName();

        $diff = $includes->diff($allowedIncludeNames);

        if ($diff->count()) {
            throw InvalidIncludeQuery::includesNotAllowed($diff, $allowedIncludeNames);
        }

        // TODO: Check for non-existing relationships?
    }
}
