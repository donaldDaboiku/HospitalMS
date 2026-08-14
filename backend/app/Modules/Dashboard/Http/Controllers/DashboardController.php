<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Patients\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('dashboard.view'), 403);

        $hospitalId = $request->user()->isSuperAdmin() ? null : $request->user()->hospital_id;

        $users = User::query()->when($hospitalId, fn ($q) => $q->where('hospital_id', $hospitalId));

        return ApiResponse::success([
            'total_users' => (clone $users)->count(),
            'active_users' => (clone $users)->where('is_active', true)->count(),
            'audit_events_today' => AuditLog::query()
                ->when($hospitalId, fn ($q) => $q->where('hospital_id', $hospitalId))
                ->whereDate('created_at', now()->toDateString())
                ->count(),
            'total_patients' => Patient::query()
                ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->count(),
            'todays_appointments' => 0,
            'waiting_patients' => 0,
            'doctors_available' => 0,
            'admissions' => 0,
            'discharges' => 0,
            'bed_occupancy' => 0,
            'pending_lab_results' => 0,
            'pending_prescriptions' => 0,
            'todays_revenue' => 0,
            'outstanding_bills' => 0,
            'low_stock' => 0,
            'emergency_cases' => 0,
        ]);
    }
}
