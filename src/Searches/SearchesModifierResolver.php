<?php

namespace Spatie\QueryBuilder\Searches;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Enums\SearchModifier;
use Spatie\QueryBuilder\Searches\Search as CustomSearch;

class SearchesModifierResolver implements Search
{
    public function __invoke(Builder $query, $value, string $property, ?string $modifier = null): Builder
    {
        return ($this->resolveSearchClass($modifier))($query, $value, $property);
    }

    private function resolveSearchClass(string $modifier): CustomSearch
    {
        if ($modifier === SearchModifier::EXACT) {
            return new SearchesExact;
        }

        if ($modifier === SearchModifier::BEGINS) {
            return new SearchesBegins;
        }

        if ($modifier === SearchModifier::ENDS) {
            return new SearchesEnds;
        }

        if ($modifier === SearchModifier::SPLIT) {
            return new SearchesSplit;
        }

        if ($modifier === SearchModifier::SPLIT_BEGINS) {
            return new SearchesSplitBegins;
        }

        if ($modifier === SearchModifier::SPLIT_ENDS) {
            return new SearchesSplitEnds;
        }

        return new SearchesPartial;
    }
}
