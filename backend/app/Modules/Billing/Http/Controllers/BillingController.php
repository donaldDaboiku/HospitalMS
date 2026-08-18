<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    public function invoices(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('billing.view'), 403);
        return ApiResponse::paginated($this->billing->invoices($request->user(), $request->query()));
    }

    public function storeInvoice(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('billing.create'), 403);
        $data = $request->validate([
            'hospital_id' => ['nullable', 'uuid'],
            'patient_id' => ['required', 'uuid'],
            'encounter_id' => ['nullable', 'uuid'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.category' => ['required', 'string', 'max:32'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.reference_type' => ['nullable', 'string', 'max:64'],
            'items.*.reference_id' => ['nullable', 'uuid'],
        ]);
        return ApiResponse::success($this->billing->createInvoice($request->user(), $data), 'Invoice created.', 201);
    }

    public function showInvoice(Invoice $invoice): JsonResponse
    {
        abort_unless(request()->user()->can('billing.view') && request()->user()->belongsToHospital($invoice->hospital_id), 403);
        return ApiResponse::success($invoice->load(['patient', 'items', 'payments', 'claims.patientInsurance.plan.provider', 'creator']));
    }

    public function issueInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($request->user()->can('billing.create') && $request->user()->belongsToHospital($invoice->hospital_id), 403);
        return ApiResponse::success($this->billing->issueInvoice($request->user(), $invoice), 'Invoice issued.');
    }

    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($request->user()->can('payment.create') && $request->user()->belongsToHospital($invoice->hospital_id), 403);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['cash', 'card', 'transfer', 'mobile', 'cheque'])],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ]);
        return ApiResponse::success($this->billing->recordPayment($request->user(), $invoice, $data), 'Payment recorded.');
    }

    public function insuranceProviders(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('insurance.view'), 403);
        return ApiResponse::paginated($this->billing->insuranceProviders($request->user(), $request->query()));
    }

    public function storeInsuranceProvider(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('insurance.create'), 403);
        $data = $request->validate([
            'hospital_id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
        ]);
        return ApiResponse::success($this->billing->createInsuranceProvider($request->user(), $data), 'Insurance provider created.', 201);
    }

    public function submitClaim(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($request->user()->can('insurance.claim') && $request->user()->belongsToHospital($invoice->hospital_id), 403);
        $data = $request->validate([
            'patient_insurance_id' => ['required', 'uuid', 'exists:patient_insurances,id'],
            'claimed_amount' => ['required', 'numeric', 'min:0.01'],
            'claim_reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ]);
        return ApiResponse::success($this->billing->submitClaim($request->user(), $invoice, $data), 'Insurance claim submitted.', 201);
    }
}
