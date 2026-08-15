<?php

namespace App\Modules\Pharmacy\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Patients\Models\Patient;
use App\Modules\Pharmacy\Models\Prescription;
use App\Modules\Pharmacy\Models\PrescriptionItem;
use App\Modules\Pharmacy\Models\Product;
use App\Modules\Pharmacy\Models\StockBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function products(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Product::query()->withSum('batches as stock_available', 'quantity_available');
        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);
        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(fn (Builder $q) => $q->where('name', $operator, $term)->orWhere('sku', $operator, $term)->orWhere('generic_name', $operator, $term));
        }
        return $query->orderBy('name')->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function createProduct(User $actor, array $data): Product
    {
        $hospitalId = $this->hospitalId($actor, $data);
        $product = Product::query()->create([...Arr::except($data, 'hospital_id'), 'hospital_id' => $hospitalId, 'sku' => strtoupper($data['sku'])]);
        $this->auditLogger->record('inventory.product_created', 'inventory', $product, newValues: $product->toArray(), user: $actor);
        return $product;
    }

    public function receiveStock(User $actor, Product $product, array $data): StockBatch
    {
        return DB::transaction(function () use ($actor, $product, $data) {
            $batch = StockBatch::query()->create([
                'hospital_id' => $product->hospital_id,
                'product_id' => $product->id,
                'batch_number' => $data['batch_number'],
                'quantity_received' => $data['quantity'],
                'quantity_available' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
            DB::table('stock_movements')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'hospital_id' => $product->hospital_id, 'product_id' => $product->id, 'stock_batch_id' => $batch->id,
                'performed_by' => $actor->id, 'type' => 'receipt', 'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->auditLogger->record('inventory.stock_received', 'inventory', $batch, newValues: $batch->toArray(), user: $actor);
            return $batch;
        });
    }

    public function prescriptions(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Prescription::query()->with(['patient:id,mrn,first_name,middle_name,last_name', 'prescriber:id,first_name,middle_name,last_name', 'items.product']);
        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        return $query->latest('prescribed_at')->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function prescribe(User $actor, array $data): Prescription
    {
        return DB::transaction(function () use ($actor, $data) {
            $hospitalId = $this->hospitalId($actor, $data);
            $patient = Patient::query()->whereKey($data['patient_id'])->where('hospital_id', $hospitalId)->first();
            if (! $patient) throw ValidationException::withMessages(['patient_id' => ['Patient was not found in this hospital.']]);
            if (! empty($data['encounter_id']) && ! Encounter::query()->whereKey($data['encounter_id'])->where('patient_id', $patient->id)->exists()) throw ValidationException::withMessages(['encounter_id' => ['Encounter does not belong to this patient.']]);
            $productIds = array_column($data['items'], 'product_id');
            if (Product::query()->where('hospital_id', $hospitalId)->where('is_active', true)->whereIn('id', $productIds)->count() !== count(array_unique($productIds))) throw ValidationException::withMessages(['items' => ['One or more medicines are invalid.']]);
            $prescription = Prescription::query()->create(['hospital_id' => $hospitalId, 'patient_id' => $patient->id, 'encounter_id' => $data['encounter_id'] ?? null, 'prescribed_by' => $actor->id, 'status' => 'prescribed', 'notes' => $data['notes'] ?? null, 'prescribed_at' => now()]);
            foreach ($data['items'] as $item) $prescription->items()->create([...$item, 'quantity_dispensed' => 0, 'status' => 'prescribed']);
            $this->auditLogger->record('pharmacy.prescription_created', 'pharmacy', $prescription, newValues: $prescription->toArray(), user: $actor);
            return $prescription->load(['patient', 'prescriber', 'items.product']);
        });
    }

    public function dispense(User $actor, PrescriptionItem $item, int $quantity): PrescriptionItem
    {
        return DB::transaction(function () use ($actor, $item, $quantity) {
            $item->load('prescription');
            $remaining = $item->quantity_prescribed - $item->quantity_dispensed;
            if ($quantity > $remaining) throw ValidationException::withMessages(['quantity' => ['Cannot dispense more than the prescribed remainder.']]);
            $batch = StockBatch::query()->where('hospital_id', $item->prescription->hospital_id)->where('product_id', $item->product_id)->where('quantity_available', '>=', $quantity)->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>', today()))->orderBy('expires_at')->lockForUpdate()->first();
            if (! $batch) throw ValidationException::withMessages(['quantity' => ['No non-expired stock batch can fulfill this dispense.']]);
            $batch->decrement('quantity_available', $quantity);
            DB::table('dispenses')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'hospital_id' => $item->prescription->hospital_id, 'prescription_item_id' => $item->id, 'stock_batch_id' => $batch->id, 'dispensed_by' => $actor->id, 'quantity' => $quantity, 'dispensed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('stock_movements')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'hospital_id' => $item->prescription->hospital_id, 'product_id' => $item->product_id, 'stock_batch_id' => $batch->id, 'performed_by' => $actor->id, 'type' => 'dispense', 'quantity' => -$quantity, 'reference_type' => PrescriptionItem::class, 'reference_id' => $item->id, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $item->increment('quantity_dispensed', $quantity);
            $item->refresh()->update(['status' => $item->quantity_dispensed >= $item->quantity_prescribed ? 'dispensed' : 'partial']);
            $item->prescription->update([
                'status' => $item->prescription->items()->whereNotIn('status', ['dispensed', 'cancelled'])->doesntExist()
                    ? 'dispensed'
                    : 'partial',
            ]);
            $this->auditLogger->record('pharmacy.dispensed', 'pharmacy', $item, newValues: ['quantity' => $quantity, 'batch_id' => $batch->id], user: $actor);
            return $item->fresh(['product', 'prescription.patient']);
        });
    }

    private function hospitalId(User $actor, array $data): string { $id = $actor->isSuperAdmin() ? ($data['hospital_id'] ?? null) : $actor->hospital_id; abort_if($id === null, 422, 'A hospital is required.'); return $id; }
    private function scopeHospital(Builder $query, User $actor, ?string $requested): void { if (! $actor->isSuperAdmin()) $query->where('hospital_id', $actor->hospital_id); elseif ($requested) $query->where('hospital_id', $requested); }
}
