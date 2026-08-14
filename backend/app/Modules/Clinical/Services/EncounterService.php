<?php

namespace App\Modules\Clinical\Services;

use App\Core\Support\Roles;
use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Clinical\Models\ClinicalNote;
use App\Modules\Clinical\Models\Diagnosis;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Clinical\Models\TriageAssessment;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EncounterService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Encounter::query()->with([
            'patient:id,mrn,first_name,middle_name,last_name',
            'doctor:id,first_name,last_name',
            'department:id,name,code',
            'triage',
            'appointment:id,scheduled_at,status',
        ]);

        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        return $query->latest('started_at')->paginate(
            min((int) ($filters['per_page'] ?? config('hms.pagination.per_page')), config('hms.pagination.max_per_page'))
        );
    }

    public function createWalkIn(User $actor, array $data): Encounter
    {
        return DB::transaction(function () use ($actor, $data) {
            $hospitalId = $this->resolveHospitalId($actor, $data);
            $patient = $this->resolvePatient($data['patient_id'], $hospitalId);
            $doctorId = $data['doctor_user_id'] ?? null;
            if ($doctorId) {
                $this->resolveDoctor($doctorId, $hospitalId);
            }
            $this->assertDepartment($data['department_id'] ?? null, $hospitalId);

            $encounter = Encounter::query()->create([
                'hospital_id' => $hospitalId,
                'patient_id' => $patient->id,
                'doctor_user_id' => $doctorId,
                'department_id' => $data['department_id'] ?? null,
                'type' => $data['type'] ?? 'OPD',
                'status' => 'open',
                'started_at' => now(),
            ]);

            $this->auditLogger->record('encounter.created', 'clinical', $encounter, newValues: $encounter->toArray(), user: $actor);

            return $encounter->load(['patient', 'doctor', 'department', 'triage', 'clinicalNotes', 'diagnoses']);
        });
    }

    public function show(Encounter $encounter): Encounter
    {
        return $encounter->load(['patient', 'doctor', 'department', 'appointment', 'triage', 'clinicalNotes.author', 'diagnoses']);
    }

    public function close(User $actor, Encounter $encounter): Encounter
    {
        if ($encounter->status === 'closed') {
            throw ValidationException::withMessages(['status' => ['Encounter is already closed.']]);
        }

        $old = $encounter->toArray();
        $encounter->fill(['status' => 'closed', 'closed_at' => now()])->save();

        if ($encounter->appointment_id) {
            Appointment::query()->whereKey($encounter->appointment_id)->whereNotIn('status', ['cancelled', 'completed'])
                ->update(['status' => 'completed']);
        }

        $this->auditLogger->record('encounter.closed', 'clinical', $encounter, oldValues: $old, newValues: $encounter->toArray(), user: $actor);

        return $this->show($encounter);
    }

    public function saveTriage(User $actor, Encounter $encounter, array $data): TriageAssessment
    {
        if ($encounter->status === 'closed') {
            throw ValidationException::withMessages(['encounter' => ['Cannot triage a closed encounter.']]);
        }

        $payload = Arr::only($data, [
            'temperature_c', 'systolic_bp', 'diastolic_bp', 'pulse', 'respiratory_rate', 'oxygen_saturation',
            'weight_kg', 'height_cm', 'pain_score', 'consciousness_level', 'allergies_noted', 'chief_complaint', 'priority',
        ]);

        $payload['bmi'] = $this->calculateBmi($payload['weight_kg'] ?? null, $payload['height_cm'] ?? null);
        $payload['hospital_id'] = $encounter->hospital_id;
        $payload['patient_id'] = $encounter->patient_id;
        $payload['assessed_by'] = $actor->id;
        $payload['assessed_at'] = now();
        $payload['priority'] = $payload['priority'] ?? 'NORMAL';

        $triage = TriageAssessment::query()->updateOrCreate(
            ['encounter_id' => $encounter->id],
            $payload
        );

        if ($encounter->status === 'open') {
            $encounter->update(['status' => 'in_progress']);
        }

        if ($encounter->appointment_id) {
            Appointment::query()->whereKey($encounter->appointment_id)
                ->whereIn('status', ['checked_in', 'scheduled', 'confirmed'])
                ->update(['status' => 'in_progress']);
        }

        $this->auditLogger->record('triage.saved', 'clinical', $triage, newValues: $triage->toArray(), user: $actor);

        return $triage->fresh(['assessor']);
    }

    public function addClinicalNote(User $actor, Encounter $encounter, array $data): ClinicalNote
    {
        if ($encounter->status === 'closed') {
            throw ValidationException::withMessages(['encounter' => ['Cannot add notes to a closed encounter.']]);
        }

        $note = ClinicalNote::query()->create([
            ...Arr::only($data, [
                'chief_complaint', 'history_of_presenting_illness', 'past_medical_history', 'family_history',
                'social_history', 'examination', 'assessment', 'treatment_plan', 'notes',
            ]),
            'hospital_id' => $encounter->hospital_id,
            'encounter_id' => $encounter->id,
            'patient_id' => $encounter->patient_id,
            'authored_by' => $actor->id,
        ]);

        $this->auditLogger->record('clinical_note.created', 'clinical', $note, newValues: Arr::only($note->toArray(), ['id', 'encounter_id', 'assessment']), user: $actor);

        return $note->load('author');
    }

    public function addDiagnosis(User $actor, Encounter $encounter, array $data): Diagnosis
    {
        if ($encounter->status === 'closed') {
            throw ValidationException::withMessages(['encounter' => ['Cannot add diagnoses to a closed encounter.']]);
        }

        $diagnosis = Diagnosis::query()->create([
            'hospital_id' => $encounter->hospital_id,
            'encounter_id' => $encounter->id,
            'patient_id' => $encounter->patient_id,
            'recorded_by' => $actor->id,
            'icd10_code' => $data['icd10_code'] ?? null,
            'description' => $data['description'],
            'type' => $data['type'] ?? 'primary',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->auditLogger->record('diagnosis.created', 'clinical', $diagnosis, newValues: $diagnosis->toArray(), user: $actor);

        return $diagnosis->load('recorder');
    }

    private function calculateBmi(mixed $weightKg, mixed $heightCm): ?float
    {
        if ($weightKg === null || $heightCm === null || (float) $heightCm <= 0) {
            return null;
        }

        $meters = (float) $heightCm / 100;

        return round(((float) $weightKg) / ($meters * $meters), 2);
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
