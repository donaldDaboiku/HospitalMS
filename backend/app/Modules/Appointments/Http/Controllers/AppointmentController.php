<?php

namespace App\Modules\Appointments\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Appointments\Http\Requests\AppointmentRequest;
use App\Modules\Appointments\Http\Resources\AppointmentResource;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Services\AppointmentService;
use App\Modules\Clinical\Http\Resources\EncounterResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointments) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        return ApiResponse::paginated(
            $this->appointments->paginate($request->user(), $request->query()),
            map: fn (Appointment $appointment) => (new AppointmentResource($appointment))->resolve(),
        );
    }

    public function store(AppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointments->create($request->user(), $request->validated());

        return ApiResponse::success(new AppointmentResource($appointment), 'Appointment booked.', 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);
        $appointment->load(['patient', 'doctor', 'department']);

        return ApiResponse::success(new AppointmentResource($appointment));
    }

    public function update(AppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointments->update($request->user(), $appointment, $request->validated());

        return ApiResponse::success(new AppointmentResource($appointment), 'Appointment updated.');
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('cancel', $appointment);
        $validated = $request->validate(['cancellation_reason' => ['nullable', 'string', 'max:255']]);
        $appointment = $this->appointments->cancel($request->user(), $appointment, $validated['cancellation_reason'] ?? null);

        return ApiResponse::success(new AppointmentResource($appointment), 'Appointment cancelled.');
    }

    public function checkIn(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);
        $result = $this->appointments->checkIn($request->user(), $appointment);

        return ApiResponse::success([
            'appointment' => (new AppointmentResource($result['appointment']))->resolve(),
            'encounter' => (new EncounterResource($result['encounter']))->resolve(),
        ], 'Patient checked in.');
    }
}
