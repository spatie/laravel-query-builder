<?php

namespace Spatie\QueryBuilder\Concerns;

use Spatie\QueryBuilder\Search;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidSearchQuery;

trait SearchesQuery
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedSearches;

    public function allowedSearches($searches): self
    {
        $searches = is_array($searches) ? $searches : func_get_args();
        $this->allowedSearches = collect($searches)->map(function ($search) {
            if ($search instanceof Search) {
                return $search;
            }

            return Search::resolver($search);
        });

        $this->guardAgainstUnknownSearches();

        $this->addSearchesToQuery($this->request->searches());

        return $this;
    }

    protected function addSearchesToQuery(Collection $searches)
    {
        $searchParameter = config('query-builder.parameters.search');

        $searches->each(function ($item, $modifier) use ($searchParameter) {
            $modifier = preg_replace(sprintf('/^%s\:?/', $searchParameter), '', $modifier, 1);
            collect($item)->each(function ($value, $property) use ($modifier) {
                if (is_string($property)) {
                    $search = $this->findSearch($property);

                    $search->search($this, $value, $modifier);
                } else {
                    $this->allowedSearches->each(function ($search) use ($value, $modifier) {
                        $search->search($this, $value, $modifier);
                    });
                }
            });
        });
    }

    protected function findSearch(string $property): ?Search
    {
        return $this->allowedSearches
            ->first(function (Search $search) use ($property) {
                return $search->isForProperty($property);
            });
    }

    protected function guardAgainstUnknownSearches()
    {
        $searchNames = $this->request->searches()
            ->filter(function ($item) {
                return is_array($item);
            })
            ->mapWithKeys(function ($item) {
                return $item;
            })->keys();

        $allowedSearchNames = $this->allowedSearches->map->getProperty();

        $diff = $searchNames->diff($allowedSearchNames);

        if ($diff->count()) {
            throw InvalidSearchQuery::searchesNotAllowed($diff, $allowedSearchNames);
        }
    }
}
