<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    use HasUuids;

    public const STATUSES = ['submitted', 'approved', 'rejected', 'paid'];

    protected $fillable = [
        'hospital_id', 'invoice_id', 'patient_insurance_id', 'submitted_by',
        'claimed_amount', 'approved_amount', 'status', 'claim_reference',
        'notes', 'submitted_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['claimed_amount' => 'decimal:2', 'approved_amount' => 'decimal:2', 'submitted_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function patientInsurance(): BelongsTo { return $this->belongsTo(PatientInsurance::class); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
}
