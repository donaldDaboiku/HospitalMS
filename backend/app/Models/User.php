<?php

namespace App\Models;

use App\Core\Support\Roles;
use App\Modules\Settings\Models\Branch;
use App\Modules\Settings\Models\Hospital;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'hospital_id',
    'branch_id',
    'first_name',
    'middle_name',
    'last_name',
    'email',
    'phone',
    'password',
    'is_active',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Roles::SUPER_ADMIN);
    }

    public function isHospitalAdmin(): bool
    {
        return $this->hasRole(Roles::HOSPITAL_ADMIN);
    }

    public function belongsToHospital(?string $hospitalId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $hospitalId !== null && $this->hospital_id === $hospitalId;
    }
}
