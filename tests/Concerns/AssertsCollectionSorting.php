<?php

namespace Spatie\QueryBuilder\Tests\Concerns;

use Illuminate\Support\Collection;

trait AssertsCollectionSorting
{
    protected function assertSortedAscending(Collection $collection, string $key)
    {
        $this->assertSorted($collection, $key);
    }

    protected function assertSortedDescending(Collection $collection, string $key)
    {
        $this->assertSorted($collection, $key, true);
    }

    protected function assertSorted(Collection $collection, string $key, bool $descending = false)
    {
        $sortedCollection = $collection->sortBy($key, SORT_REGULAR, $descending);

        $this->assertEquals($sortedCollection->pluck('id'), $collection->pluck('id'));
    }
}
