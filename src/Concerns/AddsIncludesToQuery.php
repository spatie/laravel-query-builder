<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Includes\IncludeInterface;

trait AddsIncludesToQuery
{
    protected ?Collection $allowedIncludes = null;

    public function allowedIncludes($includes): static
    {
        $includes = is_array($includes) ? $includes : func_get_args();

        $this->allowedIncludes = collect($includes)
            ->reject(function ($include) {
                return empty($include);
            })
            ->flatMap(function ($include): Collection {
                if ($include instanceof Collection) {
                    return $include;
                }

                if ($include instanceof IncludeInterface) {
                    return collect([$include]);
                }

                if (Str::endsWith($include, config('query-builder.count_suffix', 'Count'))) {
                    return AllowedInclude::count($include);
                }

                if (Str::endsWith($include, config('query-builder.exists_suffix', 'Exists'))) {
                    return AllowedInclude::exists($include);
                }

                return AllowedInclude::relationship($include);
            })
            ->unique(function (AllowedInclude $allowedInclude) {
                return $allowedInclude->getName();
            });

        $this->ensureAllIncludesExist();

        $includes = $this->filterNonExistingIncludes($this->request->includes());

        $this->addIncludesToQuery($includes);

        return $this;
    }

    protected function addIncludesToQuery(Collection $includes): void
    {
        $includes->each(function ($include) {
            $include = $this->findInclude($include);

            $include?->include($this);
        });
    }

    protected function findInclude(string $include): ?AllowedInclude
    {
        return $this->allowedIncludes
            ->first(fn (AllowedInclude $included) => $included->isForInclude($include));
    }

    protected function ensureAllIncludesExist(): void
    {
        if (config('query-builder.disable_invalid_includes_query_exception', false)) {
            return;
        }

        $includes = $this->request->includes();

        $allowedIncludeNames = $this->allowedIncludes?->map(fn (AllowedInclude $allowedInclude) => $allowedInclude->getName());

        $diff = $includes->diff($allowedIncludeNames);

        if ($diff->count()) {
            throw InvalidIncludeQuery::includesNotAllowed($diff, $allowedIncludeNames);
        }

        // TODO: Check for non-existing relationships?
    }

    /**
     * @param Collection<null|AllowedInclude> $includes
     */
    protected function filterNonExistingIncludes(Collection $includes): Collection
    {
        if (! config('query-builder.disable_invalid_includes_query_exception', false)) {
            return $includes;
        }

        return $includes->filter(fn ($include) => ! is_null($this->findInclude($include)));
    }
}
