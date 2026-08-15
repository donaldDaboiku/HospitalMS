<?php

namespace App\Modules\Radiology\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Radiology\Http\Resources\RadiologyOrderResource;
use App\Modules\Radiology\Models\RadiologyOrder;
use App\Modules\Radiology\Services\RadiologyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RadiologyController extends Controller
{
    public function __construct(private readonly RadiologyService $radiology) {}

    public function orders(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RadiologyOrder::class);

        return ApiResponse::paginated(
            $this->radiology->paginateOrders($request->user(), $request->query()),
            map: fn (RadiologyOrder $order) => (new RadiologyOrderResource($order))->resolve(),
        );
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $this->authorize('create', RadiologyOrder::class);
        $validated = $request->validate([
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'encounter_id' => ['nullable', 'uuid', 'exists:encounters,id'],
            'modality' => ['required', Rule::in(RadiologyOrder::MODALITIES)],
            'study_name' => ['required', 'string', 'max:255'],
            'priority' => ['sometimes', Rule::in(RadiologyOrder::PRIORITIES)],
            'clinical_indication' => ['nullable', 'string'],
        ]);

        return ApiResponse::success(
            new RadiologyOrderResource($this->radiology->createOrder($request->user(), $validated)),
            'Radiology order created.',
            201
        );
    }

    public function showOrder(RadiologyOrder $radiologyOrder): JsonResponse
    {
        $this->authorize('view', $radiologyOrder);

        return ApiResponse::success(new RadiologyOrderResource($this->radiology->showOrder($radiologyOrder)));
    }

    public function saveReport(Request $request, RadiologyOrder $radiologyOrder): JsonResponse
    {
        $this->authorize('report', $radiologyOrder);
        $validated = $request->validate([
            'findings' => ['required', 'string'],
            'impression' => ['nullable', 'string'],
        ]);

        $report = $this->radiology->saveReport($request->user(), $radiologyOrder, $validated);

        return ApiResponse::success($report, 'Radiology report saved.');
    }
}
