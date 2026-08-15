<?php

namespace App\Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    use HasUuids;

    protected $fillable = ['hospital_id', 'product_id', 'purchase_order_item_id', 'batch_number', 'quantity_received', 'quantity_available', 'unit_cost', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'date', 'unit_cost' => 'decimal:2'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
