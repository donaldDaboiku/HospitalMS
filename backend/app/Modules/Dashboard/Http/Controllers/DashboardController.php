<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Support\Roles;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Doctors\Models\DoctorProfile;
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
            'todays_appointments' => Appointment::query()
                ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->whereDate('scheduled_at', now()->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'waiting_patients' => Appointment::query()
                ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->where('status', 'checked_in')
                ->count(),
            'doctors_available' => DoctorProfile::query()
                ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId))
                ->where('is_available', true)
                ->whereHas('user', fn ($query) => $query->role(Roles::DOCTOR)->where('is_active', true))
                ->count(),
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
