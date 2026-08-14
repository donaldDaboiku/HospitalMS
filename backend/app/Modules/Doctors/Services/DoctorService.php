<?php

namespace App\Modules\Doctors\Services;

use App\Core\Support\Roles;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Doctors\Models\DoctorProfile;
use App\Modules\Doctors\Models\DoctorSchedule;
use App\Modules\Settings\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DoctorService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function list(User $actor, array $filters = []): Collection
    {
        $query = DoctorProfile::query()
            ->with(['user:id,first_name,middle_name,last_name,email,is_active', 'department:id,name,code'])
            ->whereHas('user', fn ($users) => $users->role(Roles::DOCTOR)->where('is_active', true));

        if (! $actor->isSuperAdmin()) {
            $query->where('hospital_id', $actor->hospital_id);
        } elseif (! empty($filters['hospital_id'])) {
            $query->where('hospital_id', $filters['hospital_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', filter_var($filters['is_available'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('specialty')->get();
    }

    public function upsertProfile(User $actor, array $data): DoctorProfile
    {
        $hospitalId = $actor->isSuperAdmin() ? ($data['hospital_id'] ?? null) : $actor->hospital_id;
        abort_if($hospitalId === null, 422, 'A hospital is required.');

        $doctor = User::query()->findOrFail($data['user_id']);
        if (! $doctor->hasRole(Roles::DOCTOR) || (! $actor->isSuperAdmin() && $doctor->hospital_id !== $hospitalId)) {
            throw ValidationException::withMessages(['user_id' => ['The selected user is not a hospital doctor.']]);
        }

        $this->assertDepartment($data['department_id'] ?? null, $hospitalId);

        $profile = DoctorProfile::query()->updateOrCreate(
            ['user_id' => $doctor->id],
            [
                'hospital_id' => $hospitalId,
                'department_id' => $data['department_id'] ?? null,
                'specialty' => $data['specialty'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'is_available' => $data['is_available'] ?? true,
            ]
        );

        $this->auditLogger->record('doctor.profile_upserted', 'doctors', $profile, newValues: $profile->toArray(), user: $actor);

        return $profile->load(['user', 'department']);
    }

    public function setSchedule(User $actor, array $data): DoctorSchedule
    {
        $hospitalId = $actor->isSuperAdmin() ? ($data['hospital_id'] ?? null) : $actor->hospital_id;
        abort_if($hospitalId === null, 422, 'A hospital is required.');

        $doctor = User::query()->findOrFail($data['doctor_user_id']);
        if (! $doctor->hasRole(Roles::DOCTOR) || (! $actor->isSuperAdmin() && $doctor->hospital_id !== $hospitalId)) {
            throw ValidationException::withMessages(['doctor_user_id' => ['The selected user is not a hospital doctor.']]);
        }

        $this->assertDepartment($data['department_id'] ?? null, $hospitalId);

        if (($data['start_time'] ?? '') >= ($data['end_time'] ?? '')) {
            throw ValidationException::withMessages(['end_time' => ['End time must be after start time.']]);
        }

        $schedule = DoctorSchedule::query()->create([
            'hospital_id' => $hospitalId,
            'doctor_user_id' => $doctor->id,
            'department_id' => $data['department_id'] ?? null,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->auditLogger->record('doctor.schedule_created', 'doctors', $schedule, newValues: $schedule->toArray(), user: $actor);

        return $schedule->load(['doctor', 'department']);
    }

    public function schedulesForDoctor(User $actor, string $doctorUserId): Collection
    {
        $query = DoctorSchedule::query()
            ->with(['department:id,name,code'])
            ->where('doctor_user_id', $doctorUserId)
            ->where('is_active', true);

        if (! $actor->isSuperAdmin()) {
            $query->where('hospital_id', $actor->hospital_id);
        }

        return $query->orderBy('day_of_week')->orderBy('start_time')->get();
    }

    private function assertDepartment(?string $departmentId, string $hospitalId): void
    {
        if ($departmentId === null) {
            return;
        }

        if (! Department::query()->whereKey($departmentId)->where('hospital_id', $hospitalId)->exists()) {
            throw ValidationException::withMessages(['department_id' => ['The selected department does not belong to this hospital.']]);
        }
    }
}
