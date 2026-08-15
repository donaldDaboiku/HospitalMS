<?php

namespace App\Modules\Pharmacy\Models;

use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $fillable = ['hospital_id', 'sku', 'name', 'generic_name', 'form', 'strength', 'unit', 'reorder_level', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'reorder_level' => 'integer'];
    }

    public function hospital(): BelongsTo { return $this->belongsTo(Hospital::class); }
    public function batches(): HasMany { return $this->hasMany(StockBatch::class); }
}
