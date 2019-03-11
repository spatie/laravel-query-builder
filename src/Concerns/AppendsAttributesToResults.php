<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;

trait AppendsAttributesToResults
{
    /** @var \Illuminate\Support\Collection */
    protected $allowedAppends;

    public function allowedAppends($appends): self
    {
        $appends = is_array($appends) ? $appends : func_get_args();

        $this->allowedAppends = collect($appends);

        $this->guardAgainstUnknownAppends();

        return $this;
    }

    protected function addAppendsToResults(Collection $results)
    {
        $appends = $this->request->appends();

        return $results->each->append($appends->toArray());
    }

    protected function guardAgainstUnknownAppends()
    {
        $appends = $this->request->appends();

        $diff = $appends->diff($this->allowedAppends);

        if ($diff->count()) {
            throw InvalidAppendQuery::appendsNotAllowed($diff, $this->allowedAppends);
        }
    }
}
