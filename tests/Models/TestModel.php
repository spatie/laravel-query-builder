<?php

namespace Spatie\QueryBuilder\Tests\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TestModel extends Model
{
    protected $guarded = [];

    public function relatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }

    public function otherRelatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function relatedThroughPivotModels(): BelongsToMany
    {
        return $this->belongsToMany(RelatedThroughPivotModel::class, 'pivot_models');
    }

    public function morphModels(): MorphMany
    {
        return $this->morphMany(MorphModel::class, 'parent');
    }

    public function scopeNamed(Builder $query, string $name) : Builder
    {
        return $query->where('name', $name);
    }

    public function scopeCreatedBetween(Builder $query, $from, $to) : Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($from), Carbon::parse($to),
        ]);
    }
}
