<?php

namespace Spatie\QueryBuilder\Tests\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MorphModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function parent(): MorphTo
    {
        return $this->morphTo();
    }
}
