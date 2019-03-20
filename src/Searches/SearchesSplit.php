<?php

namespace Spatie\QueryBuilder\Searches;

use Illuminate\Database\Eloquent\Builder;

class SearchesSplit extends SearchesBase
{
    public function __invoke(Builder $query, $value, string $property, ?string $modifier = null): Builder
    {
        if ($this->isRelationProperty($query, $property)) {
            return $this->withRelationConstraint($query, $value, $property);
        }

        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($property);

        $sql = "LOWER({$wrappedProperty}) LIKE ?";

        if (is_array($value)) {
            return $query->orWhere(function (Builder $query) use ($value, $sql) {
                foreach ($value as $partialValue) {
                    collect(explode(' ', mb_strtolower($partialValue, 'UTF8')))
                        ->each(function($partialValue) use ($query, $sql) {
                            $query->orWhereRaw($sql, [$this->encloseValue($partialValue)]);
                        });
                }
            });
        }

        collect(explode(' ', mb_strtolower($value, 'UTF8')))
            ->each(function($value) use ($query, $sql) {
                $query->orWhereRaw($sql, [$this->encloseValue($value)]);
            });

        return $query;
    }

    protected function encloseValue($value)
    {
        return "%{$value}%";
    }
}
