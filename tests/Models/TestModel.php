<?php

namespace Spatie\QueryBuilder\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TestModel extends Model
{
    protected $guarded = [];

    public function relatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function otherRelatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function scopeNamed(Builder $query, string $name) : Builder
    {
        return $query->where('name', $name);
    }

    public function scopeCreatedBetween(Builder $query, $from, $to) : Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($from), Carbon::parse($to)
        ]);
    }
}
