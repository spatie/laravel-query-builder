<?php

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Filters\Filter;
use Spatie\QueryBuilder\Filters\FiltersGroup;

it('FiltersGroup is a Filter', function () {
    $group = new FiltersGroup('or', [
        AllowedFilter::partial('name'),
    ]);

    expect($group)->toBeInstanceOf(Filter::class);
});

it('rejects an unknown conjunction', function () {
    expect(fn () => new FiltersGroup('xor', [AllowedFilter::partial('name')]))
        ->toThrow(InvalidArgumentException::class, "must be 'and' or 'or'");
});

it('rejects an empty members array', function () {
    expect(fn () => new FiltersGroup('or', []))
        ->toThrow(InvalidArgumentException::class, 'requires at least one member');
});

it('rejects non-AllowedFilter members', function () {
    expect(fn () => new FiltersGroup('or', ['name', 'full_name']))
        ->toThrow(InvalidArgumentException::class, 'must be AllowedFilter instances');
});
