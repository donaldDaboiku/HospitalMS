<?php

namespace App\Modules\Settings\Models;

use App\Models\User;
use Database\Factories\HospitalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'slug',
    'email',
    'phone',
    'address',
    'city',
    'state',
    'country',
    'settings',
    'is_active',
])]
class Hospital extends Model
{
    /** @use HasFactory<HospitalFactory> */
    use HasFactory, HasUuids;

    protected static function newFactory(): HospitalFactory
    {
        return HospitalFactory::new();
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
