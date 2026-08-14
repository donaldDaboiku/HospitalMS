<?php

namespace App\Modules\Patients\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalHistory extends Model
{
    use HasUuids;

    protected $fillable = ['condition_name', 'status', 'notes'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
