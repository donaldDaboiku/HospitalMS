<?php

namespace App\Modules\Clinical\Models;

use App\Models\User;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageAssessment extends Model
{
    use HasUuids;

    public const PRIORITIES = ['EMERGENCY', 'URGENT', 'NORMAL', 'LOW'];

    protected $fillable = [
        'hospital_id',
        'encounter_id',
        'patient_id',
        'assessed_by',
        'temperature_c',
        'systolic_bp',
        'diastolic_bp',
        'pulse',
        'respiratory_rate',
        'oxygen_saturation',
        'weight_kg',
        'height_cm',
        'bmi',
        'pain_score',
        'consciousness_level',
        'allergies_noted',
        'chief_complaint',
        'priority',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature_c' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'bmi' => 'float',
            'assessed_at' => 'datetime',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
