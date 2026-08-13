<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'hospital_id',
    'name',
    'code',
    'type',
    'description',
    'is_active',
])]
class Department extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
