<?php

namespace App\Modules\Pharmacy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasUuids;

    protected $fillable = ['prescription_id', 'product_id', 'dose', 'frequency', 'quantity_prescribed', 'quantity_dispensed', 'status', 'instructions'];

    public function prescription(): BelongsTo { return $this->belongsTo(Prescription::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
