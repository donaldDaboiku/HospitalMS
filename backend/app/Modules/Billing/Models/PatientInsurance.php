<?php

namespace App\Modules\Billing\Models;

use App\Modules\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientInsurance extends Model
{
    use HasUuids;

    protected $fillable = ['hospital_id', 'patient_id', 'insurance_plan_id', 'policy_number', 'valid_from', 'valid_to', 'is_active'];

    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_to' => 'date', 'is_active' => 'boolean'];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function plan(): BelongsTo { return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id'); }
}
