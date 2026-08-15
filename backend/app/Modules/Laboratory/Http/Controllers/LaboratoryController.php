<?php

namespace App\Modules\Laboratory\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Laboratory\Http\Resources\LabOrderResource;
use App\Modules\Laboratory\Models\LabOrder;
use App\Modules\Laboratory\Models\LabOrderItem;
use App\Modules\Laboratory\Models\LabResult;
use App\Modules\Laboratory\Models\LabTest;
use App\Modules\Laboratory\Services\LaboratoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaboratoryController extends Controller
{
    public function __construct(private readonly LaboratoryService $laboratory) {}

    public function tests(Request $request): JsonResponse
    {
        $this->authorize('viewCatalog', LabTest::class);

        return ApiResponse::success($this->laboratory->listTests($request->user(), $request->query()));
    }

    public function storeTest(Request $request): JsonResponse
    {
        $this->authorize('manageCatalog', LabTest::class);
        $validated = $request->validate([
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:64'],
            'specimen_type' => ['nullable', 'string', 'max:64'],
            'unit' => ['nullable', 'string', 'max:32'],
            'reference_range' => ['nullable', 'string', 'max:255'],
            'turnaround_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::success($this->laboratory->upsertTest($request->user(), $validated), 'Lab test saved.', 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $this->authorize('viewAnyOrders', LabOrder::class);

        return ApiResponse::paginated(
            $this->laboratory->paginateOrders($request->user(), $request->query()),
            map: fn (LabOrder $order) => (new LabOrderResource($order))->resolve(),
        );
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $this->authorize('createOrder', LabOrder::class);
        $validated = $request->validate([
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'encounter_id' => ['nullable', 'uuid', 'exists:encounters,id'],
            'lab_test_ids' => ['required', 'array', 'min:1', 'max:20'],
            'lab_test_ids.*' => ['uuid', 'exists:lab_tests,id'],
            'priority' => ['sometimes', Rule::in(LabOrder::PRIORITIES)],
            'clinical_notes' => ['nullable', 'string'],
        ]);

        $order = $this->laboratory->createOrder($request->user(), $validated);

        return ApiResponse::success(new LabOrderResource($order), 'Lab order created.', 201);
    }

    public function showOrder(LabOrder $labOrder): JsonResponse
    {
        $this->authorize('viewOrder', $labOrder);

        return ApiResponse::success(new LabOrderResource($this->laboratory->showOrder($labOrder)));
    }

    public function collect(Request $request, LabOrder $labOrder): JsonResponse
    {
        $this->authorize('collect', $labOrder);
        $validated = $request->validate([
            'specimen_type' => ['required', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            new LabOrderResource($this->laboratory->collectSpecimen($request->user(), $labOrder, $validated)),
            'Specimen collected.'
        );
    }

    public function enterResult(Request $request, LabOrderItem $labOrderItem): JsonResponse
    {
        $labOrderItem->load('order');
        $this->authorize('enterResult', $labOrderItem->order);
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:32'],
            'flag' => ['sometimes', Rule::in(LabResult::FLAGS)],
            'notes' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            $this->laboratory->enterResult($request->user(), $labOrderItem, $validated),
            'Lab result saved.'
        );
    }

    public function verifyResult(Request $request, LabResult $labResult): JsonResponse
    {
        $this->authorize('verifyResult', $labResult);

        return ApiResponse::success(
            $this->laboratory->verifyResult($request->user(), $labResult),
            'Lab result verified.'
        );
    }
}
