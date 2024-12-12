<?php

namespace Spatie\QueryBuilder\Tests\TestClasses\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class TestModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function relatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }

    public function nestedRelatedModels(): HasManyThrough
    {
        return $this->hasManyThrough(
            NestedRelatedModel::class,    // Target model
            RelatedModel::class,          // Intermediate model
            'test_model_id',              // Foreign key on RelatedModel
            'related_model_id',           // Foreign key on NestedRelatedModel
            'id',                         // Local key on TestModel
            'id'                          // Local key on RelatedModel
        );
    }

    public function otherRelatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function relatedThroughPivotModels(): BelongsToMany
    {
        return $this->belongsToMany(RelatedThroughPivotModel::class, 'pivot_models');
    }

    public function relatedThroughPivotModelsWithPivot(): BelongsToMany
    {
        return $this->belongsToMany(RelatedThroughPivotModel::class, 'pivot_models')
            ->withPivot(['location']);
    }

    public function morphModels(): MorphMany
    {
        return $this->morphMany(MorphModel::class, 'parent');
    }

    public function scopeNamed(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    public function scopeUser(Builder $query, self $user): Builder
    {
        return $query->where('id', $user->id);
    }

    public function scopeUserInfo(Builder $query, self $user, string $name): Builder
    {
        return $query
            ->where('id', $user->id)
            ->where('name', $name);
    }

    public function scopeCreatedBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($from), Carbon::parse($to),
        ]);
    }
}
