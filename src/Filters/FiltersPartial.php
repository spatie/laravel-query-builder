<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class FiltersPartial extends FiltersExact
{
    private $andSeparator;
    private $notPrefix;

    private $sqlLike;
    private $sqlNotLike;

    public function __construct(bool $addRelationConstraint = true, ?string $andSeparator = '+', ?string $notPrefix = '-')
    {
        parent::__construct($addRelationConstraint);
        $this->andSeparator = $andSeparator;
        $this->notPrefix = $notPrefix;
    }

    public function __invoke(Builder $query, $value, string $property)
    {
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));

        $this->sqlLike = "LOWER({$wrappedProperty}) LIKE ?";
        $this->sqlNotLike = "LOWER({$wrappedProperty}) NOT LIKE ?";

        $values = is_array($value) ? $value : [$value];
        $values = array_filter($values, 'strlen');
        if (count($values) === 0) {
            return $query;
        }

        if (count($values) === 1) {
            $this->generateSubQueryForLiterals($this->extractConjunctiveClauses($values[array_key_first($values)]), $query, 'and');

            return $query;
        }

        $query->where(function (Builder $query) use ($values) {
            foreach ($values as $expression) {
                $conjunctiveClauses = $this->extractConjunctiveClauses($expression);

                if (count($conjunctiveClauses) === 1) {
                    $this->generateSubQueryForLiterals($conjunctiveClauses, $query, 'or');
                } else {
                    $query->orWhere(function (Builder $andQuery) use ($conjunctiveClauses) {
                        $this->generateSubQueryForLiterals($conjunctiveClauses, $andQuery, 'and');
                    });
                }
            }
        });

        return $query;
    }

    private function extractConjunctiveClauses(string $expression)
    {
        return $this->andSeparator === null
            ? [$expression]
            : explode($this->andSeparator, $expression);
    }

    private function generateSubQueryForLiterals(array $literals, Builder $query, string $boolean)
    {
        foreach ($literals as $literal) {
            $literal = trim($literal);

            if ($literal !== '') {
                $searchString = mb_strtolower($literal, 'UTF8');

                if ($this->notPrefix === null) {
                    $negated = false;
                } else {
                    $pos = strpos($searchString, $this->notPrefix);
                    $negated = $pos !== false;
                    if ($negated) {
                        $searchString = substr_replace($searchString, '', $pos, strlen($this->notPrefix));
                    }
                }

                $query->whereRaw($negated ? $this->sqlNotLike : $this->sqlLike, ["%{$searchString}%"], $boolean);
            }
        }
    }
}
