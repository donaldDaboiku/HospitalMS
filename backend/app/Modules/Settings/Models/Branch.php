<?php

namespace App\Modules\Settings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'hospital_id',
    'name',
    'code',
    'address',
    'phone',
    'is_active',
])]
class Branch extends Model
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
