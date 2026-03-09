<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Exceptions\WildcardNotAllowedInEnvironment;
use Spatie\QueryBuilder\Includes\IncludedRelationship;

trait AddsIncludesToQuery
{
    protected ?Collection $allowedIncludes = null;

    public function allowedIncludes(AllowedInclude|string ...$includes): static
    {
        if (count($includes) === 1 && $includes[0] === '*') {
            if (! app()->environment('local', 'testing')) {
                throw WildcardNotAllowedInEnvironment::create(app()->environment());
            }

            $this->allowedIncludes = $this->request->includes()
                ->flatMap(fn (string $include) => $this->generateIncludesFromString($include))
                ->unique(fn (AllowedInclude $include) => $include->getName());

            $includes = $this->request->includes();
            $this->addIncludesToQuery($includes);

            return $this;
        }

        $this->allowedIncludes = collect($includes)
            ->reject(fn ($include) => empty($include))
            ->flatMap(function ($include): array {
                if ($include instanceof AllowedInclude) {
                    return [$include];
                }

                return $this->generateIncludesFromString($include);
            })
            ->unique(fn (AllowedInclude $allowedInclude) => $allowedInclude->getName());

        $this->ensureAllIncludesExist();

        $includes = $this->filterNonExistingIncludes($this->request->includes());

        $this->addIncludesToQuery($includes);

        return $this;
    }

    protected function generateIncludesFromString(string $include): array
    {
        $countSuffix = config('query-builder.count_suffix', 'Count');
        $existsSuffix = config('query-builder.exists_suffix', 'Exists');

        if (Str::endsWith($include, $countSuffix)) {
            return [AllowedInclude::count($include)];
        }

        if (Str::endsWith($include, $existsSuffix)) {
            return [AllowedInclude::exists($include)];
        }

        $paths = IncludedRelationship::getIndividualRelationshipPathsFromInclude($include);

        $includes = [];

        foreach ($paths as $path) {
            $includes[] = AllowedInclude::relationship($path);

            if (! Str::contains($path, '.')) {
                $includes[] = AllowedInclude::count($path.$countSuffix);
                $includes[] = AllowedInclude::exists($path.$existsSuffix);
            }
        }

        return $includes;
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
        if (config('query-builder.disable_invalid_include_query_exception', false)) {
            return;
        }

        $includes = $this->request->includes();

        $allowedIncludeNames = $this->allowedIncludes?->map(fn (AllowedInclude $allowedInclude) => $allowedInclude->getName());

        $diff = $includes->diff($allowedIncludeNames);

        if ($diff->count()) {
            throw InvalidIncludeQuery::includesNotAllowed($diff, $allowedIncludeNames);
        }
    }

    protected function filterNonExistingIncludes(Collection $includes): Collection
    {
        if (! config('query-builder.disable_invalid_include_query_exception', false)) {
            return $includes;
        }

        return $includes->filter(fn ($include) => ! is_null($this->findInclude($include)));
    }
}
