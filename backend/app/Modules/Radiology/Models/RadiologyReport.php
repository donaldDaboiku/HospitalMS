<?php

namespace App\Modules\Radiology\Models;

use App\Models\User;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'hospital_id',
        'radiology_order_id',
        'reported_by',
        'findings',
        'impression',
        'status',
        'reported_at',
    ];

    protected function casts(): array
    {
        return ['reported_at' => 'datetime'];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
