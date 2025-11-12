<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FiltersDateRange implements Filter
{
    protected array $relationConstraints = [];

    public function __construct(protected bool $addRelationConstraint = true)
    {
    }

    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        // Handle date range filter values
        // Value can be:
        // - ['from' => '2024-01-01', 'to' => '2024-12-31']
        // - ['between' => '2024-01-01,2024-12-31']
        // - ['from' => '2024-01-01']
        // - ['to' => '2024-12-31']

        if (! is_array($value)) {
            return;
        }

        $from = $value['from'] ?? null;
        $to = $value['to'] ?? null;
        $between = $value['between'] ?? null;

        // Handle 'between' syntax: between=2024-01-01,2024-12-31
        if ($between !== null) {
            $dates = is_string($between) ? explode(',', $between) : $between;
            if (count($dates) === 2) {
                $from = trim($dates[0]);
                $to = trim($dates[1]);
            }
        }

        $qualifiedColumn = $query->qualifyColumn($property);

        // Apply date range filters
        // Use whereDate to ensure we match the entire day, regardless of time component
        if ($from !== null && $to !== null) {
            // Both from and to: use whereDate with >= and <= to include full days
            $query->whereDate($qualifiedColumn, '>=', $from)
                  ->whereDate($qualifiedColumn, '<=', $to);
        } elseif ($from !== null) {
            // Only from: use whereDate >=
            $query->whereDate($qualifiedColumn, '>=', $from);
        } elseif ($to !== null) {
            // Only to: use whereDate <=
            $query->whereDate($qualifiedColumn, '<=', $to);
        }
    }

    protected function isRelationProperty(Builder $query, string $property): bool
    {
        if (! Str::contains($property, '.')) {
            return false;
        }

        if (in_array($property, $this->relationConstraints)) {
            return false;
        }

        $firstRelationship = explode('.', $property)[0];

        if (! method_exists($query->getModel(), $firstRelationship)) {
            return false;
        }

        return is_a($query->getModel()->{$firstRelationship}(), Relation::class);
    }

    protected function withRelationConstraint(Builder $query, mixed $value, string $property): void
    {
        [$relation, $property] = collect(explode('.', $property))
            ->pipe(fn ($parts) => [
                $parts->except(count($parts) - 1)->implode('.'),
                $parts->last(),
            ]);

        $query->whereHas($relation, function (Builder $query) use ($property, $value) {
            /** @var Builder<TModelClass> $query */
            $this->relationConstraints[] = $property = $query->qualifyColumn($property);

            $this->__invoke($query, $value, $property);
        });
    }
}

