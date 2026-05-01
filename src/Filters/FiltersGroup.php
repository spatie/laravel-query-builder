<?php

namespace Spatie\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @template-implements Filter<\Illuminate\Database\Eloquent\Model>
 */
class FiltersGroup implements Filter
{
    /** @var AllowedFilter[] */
    protected array $members;

    /** @param AllowedFilter[] $members */
    public function __construct(
        protected string $conjunction,
        array $members,
    ) {
        if (! in_array($conjunction, ['and', 'or'], true)) {
            throw new InvalidArgumentException(
                "FiltersGroup conjunction must be 'and' or 'or', got '{$conjunction}'."
            );
        }

        if ($members === []) {
            throw new InvalidArgumentException('FiltersGroup requires at least one member.');
        }

        foreach ($members as $member) {
            if (! $member instanceof AllowedFilter) {
                throw new InvalidArgumentException(
                    'FiltersGroup members must be AllowedFilter instances, got '.get_debug_type($member).'.'
                );
            }
        }

        $this->members = $members;
    }

    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->where(function (Builder $sub) use ($value) {
            foreach ($this->members as $member) {
                if ($this->conjunction === 'or') {
                    $sub->orWhere(function (Builder $inner) use ($member, $value) {
                        $member->applyTo($inner, $value);
                    });
                } else {
                    $sub->where(function (Builder $inner) use ($member, $value) {
                        $member->applyTo($inner, $value);
                    });
                }
            }
        });
    }
}
