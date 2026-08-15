<?php

namespace App\Modules\Radiology\Models;

use App\Models\User;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RadiologyOrder extends Model
{
    use HasUuids;

    public const STATUSES = ['ordered', 'in_progress', 'reported', 'cancelled'];

    public const PRIORITIES = ['routine', 'urgent', 'stat'];

    public const MODALITIES = ['XRAY', 'CT', 'MRI', 'US', 'MG', 'FLUORO', 'OTHER'];

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'encounter_id',
        'ordered_by',
        'modality',
        'study_name',
        'status',
        'priority',
        'clinical_indication',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return ['ordered_at' => 'datetime'];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function report(): HasOne
    {
        return $this->hasOne(RadiologyReport::class);
    }
}
