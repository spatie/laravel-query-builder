<?php
namespace Spatie\QueryBuilder\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class RelatedModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function testModel(): BelongsTo
    {
        return $this->belongsTo(TestModel::class);
    }
}
