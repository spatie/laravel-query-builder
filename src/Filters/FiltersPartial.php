<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Concerns\HandlesRelationConstraints;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements Filter<TModelClass>
 */
class FiltersPartial implements Filter
{
    use HandlesRelationConstraints;

    public function __construct(protected bool $addRelationConstraint = true)
    {
    }

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if ($this->addRelationConstraint && $this->isRelationProperty($query, $property)) {
            $this->withRelationConstraint($query, $value, $property);

            return;
        }

        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));
        $databaseDriver = $this->getDatabaseDriver($query);

        if (is_array($value)) {
            if (count(array_filter($value, fn ($item) => $item != '')) === 0) {
                return;
            }

            $query->where(function (Builder $query) use ($value, $wrappedProperty, $databaseDriver) {
                foreach (array_filter($value, fn ($item) => $item != '') as $partialValue) {
                    [$sql, $bindings] = $this->getWhereRawParameters($partialValue, $wrappedProperty, $databaseDriver);
                    $query->orWhereRaw($sql, $bindings);
                }
            });

            return;
        }

        [$sql, $bindings] = $this->getWhereRawParameters($value, $wrappedProperty, $databaseDriver);
        $query->whereRaw($sql, $bindings);
    }

    protected function getDatabaseDriver(Builder $query): string
    {
        return $query->getConnection()->getDriverName(); /** @phpstan-ignore-line */
    }

    protected function getWhereRawParameters(mixed $value, string $property, string $driver): array
    {
        $value = mb_strtolower((string) $value, 'UTF8');

        return [
            "LOWER({$property}) LIKE ?".self::maybeSpecifyEscapeChar($driver),
            ['%'.self::escapeLike($value).'%'],
        ];
    }

    protected static function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '_', '%'],
            ['\\\\', '\\_', '\\%'],
            $value,
        );
    }

    /**
     * @param 'sqlite'|'pgsql'|'sqlsrc'|'mysql'|'mariadb' $driver
     */
    protected static function maybeSpecifyEscapeChar(string $driver): string
    {
        if (! in_array($driver, ['sqlite', 'sqlsrv'])) {
            return '';
        }

        return " ESCAPE '\'";
    }
}
