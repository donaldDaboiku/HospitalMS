<?php

namespace App\Modules\Appointments\Services;

use App\Core\Support\Roles;
use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Appointment::query()->with([
            'patient:id,mrn,first_name,middle_name,last_name,date_of_birth,phone',
            'doctor:id,first_name,last_name,email',
            'department:id,name,code',
        ]);

        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);

        if (! empty($filters['date'])) {
            $query->whereDate('scheduled_at', $filters['date']);
        } elseif (($filters['scope'] ?? null) === 'today') {
            $query->whereDate('scheduled_at', now()->toDateString());
        }

        if (! empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : explode(',', (string) $filters['status']);
            $query->whereIn('status', $statuses);
        }

        if (! empty($filters['doctor_user_id'])) {
            $query->where('doctor_user_id', $filters['doctor_user_id']);
        }

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        return $query->orderBy('scheduled_at')->paginate(
            min((int) ($filters['per_page'] ?? config('hms.pagination.per_page')), config('hms.pagination.max_per_page'))
        );
    }

    public function create(User $actor, array $data): Appointment
    {
        return DB::transaction(function () use ($actor, $data) {
            $hospitalId = $this->resolveHospitalId($actor, $data);
            $patient = $this->resolvePatient($data['patient_id'], $hospitalId);
            $doctor = $this->resolveDoctor($data['doctor_user_id'], $hospitalId);
            $this->assertDepartment($data['department_id'] ?? null, $hospitalId);

            $appointment = Appointment::query()->create([
                'hospital_id' => $hospitalId,
                'branch_id' => $data['branch_id'] ?? $patient->branch_id,
                'patient_id' => $patient->id,
                'doctor_user_id' => $doctor->id,
                'department_id' => $data['department_id'] ?? null,
                'scheduled_by' => $actor->id,
                'scheduled_at' => $data['scheduled_at'],
                'status' => $data['status'] ?? 'scheduled',
                'type' => $data['type'] ?? 'scheduled',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->auditLogger->record('appointment.created', 'appointments', $appointment, newValues: $appointment->toArray(), user: $actor);

            return $appointment->load(['patient', 'doctor', 'department']);
        });
    }

    public function update(User $actor, Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $data) {
            $old = $appointment->toArray();

            if (isset($data['doctor_user_id'])) {
                $this->resolveDoctor($data['doctor_user_id'], $appointment->hospital_id);
            }
            if (array_key_exists('department_id', $data)) {
                $this->assertDepartment($data['department_id'], $appointment->hospital_id);
            }

            $appointment->fill(Arr::only($data, [
                'doctor_user_id', 'department_id', 'scheduled_at', 'status', 'type', 'reason', 'notes',
            ]))->save();

            $this->auditLogger->record('appointment.updated', 'appointments', $appointment, oldValues: $old, newValues: $appointment->fresh()->toArray(), user: $actor);

            return $appointment->fresh(['patient', 'doctor', 'department']);
        });
    }

    public function cancel(User $actor, Appointment $appointment, ?string $reason = null): Appointment
    {
        if (in_array($appointment->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => ['This appointment can no longer be cancelled.']]);
        }

        $old = $appointment->toArray();
        $appointment->fill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ])->save();

        $this->auditLogger->record('appointment.cancelled', 'appointments', $appointment, oldValues: $old, newValues: $appointment->toArray(), user: $actor);

        return $appointment->fresh(['patient', 'doctor', 'department']);
    }

    public function checkIn(User $actor, Appointment $appointment): array
    {
        return DB::transaction(function () use ($actor, $appointment) {
            if (! in_array($appointment->status, ['scheduled', 'confirmed'], true)) {
                throw ValidationException::withMessages(['status' => ['Only scheduled or confirmed appointments can be checked in.']]);
            }

            $old = $appointment->toArray();
            $appointment->fill([
                'status' => 'checked_in',
                'checked_in_at' => now(),
            ])->save();

            $encounter = Encounter::query()->firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'hospital_id' => $appointment->hospital_id,
                    'patient_id' => $appointment->patient_id,
                    'doctor_user_id' => $appointment->doctor_user_id,
                    'department_id' => $appointment->department_id,
                    'type' => 'OPD',
                    'status' => 'open',
                    'started_at' => now(),
                ]
            );

            $this->auditLogger->record('appointment.checked_in', 'appointments', $appointment, oldValues: $old, newValues: $appointment->toArray(), user: $actor);

            return [
                'appointment' => $appointment->fresh(['patient', 'doctor', 'department']),
                'encounter' => $encounter->load(['patient', 'doctor', 'department', 'triage']),
            ];
        });
    }

    private function resolveHospitalId(User $actor, array $data): string
    {
        $hospitalId = $actor->isSuperAdmin() ? ($data['hospital_id'] ?? null) : $actor->hospital_id;
        abort_if($hospitalId === null, 422, 'A hospital is required.');

        return $hospitalId;
    }

    private function resolvePatient(string $patientId, string $hospitalId): Patient
    {
        $patient = Patient::query()->whereKey($patientId)->where('hospital_id', $hospitalId)->first();
        if ($patient === null) {
            throw ValidationException::withMessages(['patient_id' => ['The selected patient was not found in this hospital.']]);
        }

        return $patient;
    }

    private function resolveDoctor(string $doctorUserId, string $hospitalId): User
    {
        $doctor = User::query()->whereKey($doctorUserId)->where('hospital_id', $hospitalId)->first();
        if ($doctor === null || ! $doctor->hasRole(Roles::DOCTOR)) {
            throw ValidationException::withMessages(['doctor_user_id' => ['The selected doctor was not found in this hospital.']]);
        }

        return $doctor;
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

    private function scopeHospital(Builder $query, User $actor, ?string $requestedHospitalId): void
    {
        if (! $actor->isSuperAdmin()) {
            $query->where('hospital_id', $actor->hospital_id);
        } elseif ($requestedHospitalId !== null) {
            $query->where('hospital_id', $requestedHospitalId);
        }
    }
}
