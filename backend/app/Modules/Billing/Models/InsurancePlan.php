<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurancePlan extends Model
{
    use HasUuids;

    protected $fillable = ['insurance_provider_id', 'name', 'coverage_percent', 'max_coverage', 'is_active'];

    protected function casts(): array
    {
        return ['coverage_percent' => 'decimal:2', 'max_coverage' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function provider(): BelongsTo { return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id'); }
}
