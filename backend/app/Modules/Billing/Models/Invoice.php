<?php

namespace App\Modules\Billing\Models;

use App\Models\User;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasUuids;

    public const STATUSES = ['draft', 'issued', 'partial', 'paid', 'cancelled', 'refunded'];

    protected $fillable = [
        'hospital_id', 'patient_id', 'encounter_id', 'created_by',
        'invoice_number', 'status', 'subtotal', 'discount', 'tax', 'total',
        'amount_paid', 'notes', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'tax' => 'decimal:2',
            'total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'issued_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function claims(): HasMany { return $this->hasMany(InsuranceClaim::class); }
}
