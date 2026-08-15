<?php

namespace App\Modules\Pharmacy\Models;

use App\Models\User;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasUuids;

    protected $fillable = ['hospital_id', 'patient_id', 'encounter_id', 'prescribed_by', 'status', 'notes', 'prescribed_at'];

    protected function casts(): array { return ['prescribed_at' => 'datetime']; }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function prescriber(): BelongsTo { return $this->belongsTo(User::class, 'prescribed_by'); }
    public function items(): HasMany { return $this->hasMany(PrescriptionItem::class); }
}
