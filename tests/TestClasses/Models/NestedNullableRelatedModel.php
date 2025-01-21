<?php

namespace Spatie\QueryBuilder\Tests\TestClasses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NestedNullableRelatedModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }
}
