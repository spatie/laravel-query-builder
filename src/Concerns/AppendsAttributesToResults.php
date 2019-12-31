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
	    $appends = $this->request->appends();
	    return $results->each(function($item) use($appends)
	    {
	        $appends->each(function($append) use($item)
	        {
	            if(strpos($append, '.'))
	            {
	                $parts = explode('.', $append);
	                $relation = $parts[0];
	                $appending = $parts[1];
	                if($item->$relation !== null)
	                {
	                    if($item->$relation instanceof \Illuminate\Database\Eloquent\Collection)
	                    {
	                        $item->$relation()->get()->each->append($appending);
	                    } else
	                    {
	                        $item->$relation->append($appending);
	                    }
	                }
	            } else
	            {
	                $item->append($append);
	            }
	        });
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
