<?php

namespace App\Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'lab_order_id',
        'lab_test_id',
        'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class);
    }
}
