<?php

namespace App\Modules\Doctors\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Doctors\Services\DoctorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function __construct(private readonly DoctorService $doctors) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('appointment.view') || $request->user()->can('clinical.view'), 403);

        return ApiResponse::success($this->doctors->list($request->user(), $request->query()));
    }

    public function upsertProfile(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('user.edit') || $request->user()->can('settings.manage'), 403);
        $validated = $request->validate([
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:64'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::success($this->doctors->upsertProfile($request->user(), $validated), 'Doctor profile saved.');
    }

    public function schedules(Request $request, string $doctorUserId): JsonResponse
    {
        abort_unless($request->user()->can('appointment.view'), 403);

        return ApiResponse::success($this->doctors->schedulesForDoctor($request->user(), $doctorUserId));
    }

    public function storeSchedule(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('user.edit') || $request->user()->can('settings.manage'), 403);
        $validated = $request->validate([
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'doctor_user_id' => ['required', 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::success($this->doctors->setSchedule($request->user(), $validated), 'Doctor schedule saved.', 201);
    }
}
