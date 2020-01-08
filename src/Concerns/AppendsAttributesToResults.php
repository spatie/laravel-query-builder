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
		        	$subs = collect(explode('.', $append));
		        	$relation = $subs->shift();

		        	$item = $this->appendLoop($item, $relation, $subs);
	            } else
	            {
	                $item->append($append);
	            }
	        });
	    });
	}

	private function appendLoop($item, $relation, $subs)
	{
		if($item->$relation !== null)
	    {
	    	if($subs->count() === 1)
	    	{
	    		$sub = $subs->first();
		        if($item->$relation instanceof \Illuminate\Database\Eloquent\Collection)
		        {
		            $item->$relation->each(function($model) use($sub)
		            	{
		            		$model->append($sub);
		            	});
		        } else
		        {
		            $item->$relation->append($sub);
	       		}
	    	} else
	    	{
	    		$sub = $subs->shift();
	    		$item->$relation = $this->appendLoop($item->$relation, $sub, $subs);
	    	}
	    }
	    return $item;
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
