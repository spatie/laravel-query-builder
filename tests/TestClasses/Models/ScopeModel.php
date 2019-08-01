<?php

namespace Spatie\QueryBuilder\Tests\TestClasses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ScopeModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('nameNotTest', function (Builder $builder) {
            $builder->where('name', '<>', 'test');
        });
    }
}
