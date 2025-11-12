<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @template-implements \Spatie\QueryBuilder\Filters\Filter<TModelClass>
 */
class FiltersPartial extends FiltersExact implements Filter
{
    /** {@inheritdoc} */
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($this->addRelationConstraint) {
            if ($this->isRelationProperty($query, $property)) {
                $this->withRelationConstraint($query, $value, $property);

                return;
            }
        }

        // Check if this is a JSON column filter (contains ->)
        if ($this->isJsonColumn($property)) {
            $this->applyJsonColumnFilter($query, $value, $property);

            return;
        }

        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));
        $databaseDriver = $this->getDatabaseDriver($query);

        if (is_array($value)) {
            if (count(array_filter($value, fn ($item) => $item != '')) === 0) {
                return $query;
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

    protected function isJsonColumn(string $property): bool
    {
        return \Illuminate\Support\Str::contains($property, '->');
    }

    protected function applyJsonColumnFilter(Builder $query, mixed $value, string $property): void
    {
        $wrappedProperty = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));
        $databaseDriver = $this->getDatabaseDriver($query);

        // For JSON columns with partial matching, we need to use JSON extraction
        // Laravel supports JSON_EXTRACT or JSON_UNQUOTE(JSON_EXTRACT) depending on the driver
        if (is_array($value)) {
            if (count(array_filter($value, fn ($item) => $item != '')) === 0) {
                return;
            }

            $query->where(function (Builder $query) use ($value, $wrappedProperty, $databaseDriver, $property) {
                foreach (array_filter($value, fn ($item) => $item != '') as $partialValue) {
                    // Use JSON extraction for partial matching on JSON columns
                    $jsonPath = $this->convertJsonPathToSql($query, $property, $databaseDriver);
                    [$sql, $bindings] = $this->getWhereRawParameters($partialValue, $jsonPath, $databaseDriver);
                    $query->orWhereRaw($sql, $bindings);
                }
            });

            return;
        }

        // For single value, use JSON extraction with LIKE
        $jsonPath = $this->convertJsonPathToSql($query, $property, $databaseDriver);
        [$sql, $bindings] = $this->getWhereRawParameters($value, $jsonPath, $databaseDriver);
        $query->whereRaw($sql, $bindings);
    }

    protected function convertJsonPathToSql(Builder $query, string $property, string $driver): string
    {
        // Convert metadata->key to JSON_EXTRACT(column, '$.key') or equivalent
        [$column, $path] = explode('->', $property, 2);
        $qualifiedColumn = $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));

        // Build JSON path: convert key->nested->0 to $.key.nested[0]
        $jsonPath = '$';
        $parts = explode('->', $path);
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $jsonPath .= '[' . $part . ']';
            } else {
                $jsonPath .= '.' . $part;
            }
        }

        // Database-specific JSON extraction
        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                return "LOWER(JSON_UNQUOTE(JSON_EXTRACT({$qualifiedColumn}, '{$jsonPath}')))";
            case 'pgsql':
                // PostgreSQL uses -> operator for JSON
                // Convert path parts to PostgreSQL JSON path syntax
                $pgPath = str_replace('->', '->>', $path);
                return "LOWER(({$qualifiedColumn}->>'{$pgPath}')::text)";
            case 'sqlite':
                return "LOWER(json_extract({$qualifiedColumn}, '{$jsonPath}'))";
            default:
                // Fallback: use Laravel's JSON column syntax which should work
                return $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($property));
        }
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
     * @return string
     */
    protected static function maybeSpecifyEscapeChar(string $driver): string
    {
        if (! in_array($driver, ['sqlite','sqlsrv'])) {
            return '';
        }

        return " ESCAPE '\'";
    }
}
