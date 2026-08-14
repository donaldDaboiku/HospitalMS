<?php

namespace App\Modules\Clinical\Models;

use App\Models\User;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalNote extends Model
{
    use HasUuids;

    protected $fillable = [
        'hospital_id',
        'encounter_id',
        'patient_id',
        'authored_by',
        'chief_complaint',
        'history_of_presenting_illness',
        'past_medical_history',
        'family_history',
        'social_history',
        'examination',
        'assessment',
        'treatment_plan',
        'notes',
    ];

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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authored_by');
    }
}
