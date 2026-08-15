<?php

namespace App\Modules\Laboratory\Models;

use App\Models\User;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    use HasUuids;

    public const FLAGS = ['normal', 'low', 'high', 'critical', 'abnormal'];

    public const STATUSES = ['preliminary', 'final'];

    protected $fillable = [
        'hospital_id',
        'lab_order_item_id',
        'patient_id',
        'entered_by',
        'verified_by',
        'value',
        'unit',
        'flag',
        'status',
        'notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LabOrderItem::class, 'lab_order_item_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
