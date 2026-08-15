<?php

namespace App\Modules\Laboratory\Models;

use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTest extends Model
{
    use HasUuids;

    protected $fillable = [
        'hospital_id',
        'code',
        'name',
        'category',
        'specimen_type',
        'unit',
        'reference_range',
        'turnaround_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'turnaround_hours' => 'integer',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }
}
