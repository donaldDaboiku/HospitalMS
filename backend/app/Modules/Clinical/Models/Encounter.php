<?php

namespace App\Modules\Clinical\Models;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Department;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Encounter extends Model
{
    use HasUuids;

    public const TYPES = ['OPD', 'IPD', 'EMERGENCY', 'FOLLOW_UP', 'TELEMEDICINE'];

    public const STATUSES = ['open', 'in_progress', 'closed', 'cancelled'];

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'appointment_id',
        'doctor_user_id',
        'department_id',
        'type',
        'status',
        'started_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function triage(): HasOne
    {
        return $this->hasOne(TriageAssessment::class);
    }

    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }
}
