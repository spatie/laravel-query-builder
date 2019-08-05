<?php

namespace Spatie\QueryBuilder\Tests\TestClasses\Models;

use Illuminate\Database\Eloquent\Model;

class AppendModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function getFullnameAttribute()
    {
        return $this->firstname.' '.$this->lastname;
    }

    public function getReversenameAttribute()
    {
        return $this->lastname.' '.$this->firstname;
    }
}
