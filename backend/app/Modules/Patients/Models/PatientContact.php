<?php

namespace App\Modules\Patients\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientContact extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'related_patient_id',
        'full_name',
        'relationship',
        'phone',
        'email',
        'address',
        'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function relatedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'related_patient_id');
    }
}
