<?php

namespace App\Modules\Pharmacy\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Pharmacy\Models\Prescription;
use App\Modules\Pharmacy\Models\PrescriptionItem;
use App\Modules\Pharmacy\Models\Product;
use App\Modules\Pharmacy\Services\PharmacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function __construct(private readonly PharmacyService $pharmacy) {}

    public function products(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('inventory.view') || $request->user()->can('pharmacy.prescribe'), 403);
        return ApiResponse::paginated($this->pharmacy->products($request->user(), $request->query()));
    }

    public function storeProduct(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('inventory.create'), 403);
        $data = $request->validate(['hospital_id' => ['nullable','uuid'], 'sku' => ['required','string','max:64'], 'name' => ['required','string','max:255'], 'generic_name' => ['nullable','string','max:255'], 'form' => ['nullable','string','max:64'], 'strength' => ['nullable','string','max:64'], 'unit' => ['nullable','string','max:32'], 'reorder_level' => ['sometimes','integer','min:0']]);
        return ApiResponse::success($this->pharmacy->createProduct($request->user(), $data), 'Product created.', 201);
    }

    public function receive(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()->can('inventory.create') && $request->user()->belongsToHospital($product->hospital_id), 403);
        $data = $request->validate(['batch_number' => ['required','string','max:64'], 'quantity' => ['required','integer','min:1'], 'unit_cost' => ['nullable','numeric','min:0'], 'expires_at' => ['nullable','date','after:today'], 'notes' => ['nullable','string']]);
        return ApiResponse::success($this->pharmacy->receiveStock($request->user(), $product, $data), 'Stock received.', 201);
    }

    public function prescriptions(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('pharmacy.prescribe') || $request->user()->can('pharmacy.dispense'), 403);
        return ApiResponse::paginated($this->pharmacy->prescriptions($request->user(), $request->query()));
    }

    public function prescribe(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('pharmacy.prescribe'), 403);
        $data = $request->validate(['hospital_id' => ['nullable','uuid'], 'patient_id' => ['required','uuid'], 'encounter_id' => ['nullable','uuid'], 'notes' => ['nullable','string'], 'items' => ['required','array','min:1','max:20'], 'items.*.product_id' => ['required','uuid'], 'items.*.dose' => ['nullable','string','max:255'], 'items.*.frequency' => ['nullable','string','max:255'], 'items.*.quantity_prescribed' => ['required','integer','min:1'], 'items.*.instructions' => ['nullable','string']]);
        return ApiResponse::success($this->pharmacy->prescribe($request->user(), $data), 'Prescription created.', 201);
    }

    public function dispense(Request $request, PrescriptionItem $prescriptionItem): JsonResponse
    {
        $prescriptionItem->load('prescription');
        abort_unless($request->user()->can('pharmacy.dispense') && $request->user()->belongsToHospital($prescriptionItem->prescription->hospital_id), 403);
        $data = $request->validate(['quantity' => ['required','integer','min:1']]);
        return ApiResponse::success($this->pharmacy->dispense($request->user(), $prescriptionItem, $data['quantity']), 'Medication dispensed.');
    }
}
