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
        return $results->each(function ($result) {
            if ($result instanceof Model) {
                return $result->append($this->request->appends()->toArray());
            }
        });
    }

    protected function addAppendsToCursor($results)
    {
        return $results->each(function ($result) {
            if ($result instanceof Model) {
                return $result->append($this->request->appends()->toArray());
            }
        });
    }

    protected function ensureAllAppendsExist()
    {
        $appends = $this->request->appends();

        $diff = $appends->diff($this->allowedAppends);

        if ($diff->count()) {
            throw InvalidAppendQuery::appendsNotAllowed($diff, $this->allowedAppends);
        }
    }
}
