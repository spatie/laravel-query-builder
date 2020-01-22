<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Database\Eloquent\Model;
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

        $this->ensureAllAppendsExist();

        return $this;
    }

    protected function addAppendsToResults(Collection $results)
    {
        if (! $this->ensureAllAppendsExist()) {
            return $results;
        }

        return $results->each(function (Model $result) {
            return $result->append($this->request->appends()->toArray());
        });
    }

    protected function ensureAllAppendsExist(): bool
    {
        $appends = $this->request->appends();

        $diff = $appends->diff($this->allowedAppends);

        if ($diff->count()) {
            if ($this->throwInvalidQueryExceptions) {
                throw InvalidAppendQuery::appendsNotAllowed($diff, $this->allowedAppends);
            } else {
                return false;
            }
        }

        return true;
    }
}
