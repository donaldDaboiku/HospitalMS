<?php

namespace App\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceProvider extends Model
{
    use HasUuids;

    protected $fillable = ['hospital_id', 'name', 'code', 'phone', 'email', 'address', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function plans(): HasMany { return $this->hasMany(InsurancePlan::class); }
}
