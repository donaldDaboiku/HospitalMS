<?php

namespace App\Modules\Laboratory\Models;

use App\Models\User;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabSpecimen extends Model
{
    use HasUuids;

    protected $fillable = [
        'hospital_id',
        'lab_order_id',
        'patient_id',
        'collected_by',
        'specimen_type',
        'status',
        'collected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return ['collected_at' => 'datetime'];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
