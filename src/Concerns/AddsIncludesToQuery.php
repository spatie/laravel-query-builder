<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Includes\IncludeInterface;

trait AddsIncludesToQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedIncludes;

    public function allowedIncludes($includes): self
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

                if (Str::endsWith($include, config('query-builder.count_suffix'))) {
                    return AllowedInclude::count($include);
                }

                return AllowedInclude::relationship($include);
            })
            ->unique(function (AllowedInclude $allowedInclude) {
                return $allowedInclude->getName();
            });

        $this->ensureAllIncludesExist();

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

    protected function ensureAllIncludesExist()
    {
        $includes = $this->request->includes();

        $allowedIncludeNames = $this->allowedIncludes->map(function (AllowedInclude $allowedInclude) {
            return $allowedInclude->getName();
        });

        $diff = $includes->diff($allowedIncludeNames);

        if ($diff->count()) {
            throw InvalidIncludeQuery::includesNotAllowed($diff, $allowedIncludeNames);
        }

        $this->ensureRelationshipsExist($this->getModel(), $includes, $allowedIncludeNames);
    }

    protected function ensureRelationshipsExist($model, $includes, $allowedIncludeNames)
    {
        $includes->map(function ($include) {
            return [$this->getInternalName($include), $include];
        })->eachSpread(function ($relationships, $include) use ($model, $allowedIncludeNames) {
            $relationships = explode('.', Str::of($relationships)->before('Count'), 2);

            if (count($relationships) > 1) {
                $this->ensureRelationshipsExist($model->{$relationships[0]}()->getRelated(), collect($relationships[1]), $allowedIncludeNames);
            }

            $availableRelationships = collect(get_class_methods($model))->reject(function ($method) {
                return method_exists(Model::class, $method);
            });

            if (! $availableRelationships->contains($relationships[0])) {
                throw InvalidIncludeQuery::includesNotAllowed(collect($include), $allowedIncludeNames);
            }
        });
    }

    protected function getInternalName($include)
    {
        return optional($this->allowedIncludes->first(function (AllowedInclude $allowedInclude) use ($include) {
            return $allowedInclude->getName() === $include;
        }))->getInterlName() ?? $include;
    }
}
