<?php

namespace App\Modules\Audit\Models;

use App\Models\User;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'hospital_id',
    'user_id',
    'action',
    'module',
    'auditable_type',
    'auditable_id',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
    'url',
    'method',
    'request_id',
    'created_at',
])]
class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
