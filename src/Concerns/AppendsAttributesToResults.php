<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        return $results->each(function($item) use($appends) {
            $appends->each(function($append) use($item) {
                if(Str::contains($append, '.')) {
                    $nestedAppends = collect(explode('.', $append));
                    $relation = $nestedAppends->shift();

                    $this->appendLoop($item, $relation, $nestedAppends);
                } else {
                    $item->append($append);
                }
            });
        });
    }

    private function appendLoop($item, string $relation, Collection $nestedAppends) {
        if($item->relationLoaded($relation)) {
            if($nestedAppends->count() === 1) {
                $sub = $nestedAppends->first();
                if($item->$relation instanceof \Illuminate\Database\Eloquent\Collection) {
                    $item->$relation->each(function($model) use($sub) {
                        $model->append($sub);
                    });
                } else {
                    $item->$relation->append($sub);
                }
            } else {
                $sub = $nestedAppends->shift();
                $this->appendLoop($item->$relation, $sub, $nestedAppends);
            }
        }
    }

    protected function ensureAllAppendsExist() {
        $appends = $this->request->appends();

        $diff = $appends->diff($this->allowedAppends);

        if ($diff->count()) {
            throw InvalidAppendQuery::appendsNotAllowed($diff, $this->allowedAppends);
        }
    }
}
