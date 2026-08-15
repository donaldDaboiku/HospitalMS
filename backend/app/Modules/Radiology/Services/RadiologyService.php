<?php

namespace App\Modules\Radiology\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Patients\Models\Patient;
use App\Modules\Radiology\Models\RadiologyOrder;
use App\Modules\Radiology\Models\RadiologyReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RadiologyService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function paginateOrders(User $actor, array $filters): LengthAwarePaginator
    {
        $query = RadiologyOrder::query()->with([
            'patient:id,mrn,first_name,middle_name,last_name',
            'orderedBy:id,first_name,middle_name,last_name',
            'report',
        ]);
        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('ordered_at')->paginate(
            min((int) ($filters['per_page'] ?? config('hms.pagination.per_page')), config('hms.pagination.max_per_page'))
        );
    }

    public function createOrder(User $actor, array $data): RadiologyOrder
    {
        return DB::transaction(function () use ($actor, $data) {
            $hospitalId = $this->resolveHospitalId($actor, $data);
            $patient = $this->resolvePatient($data['patient_id'], $hospitalId);
            $this->assertEncounter($data['encounter_id'] ?? null, $patient);

            $order = RadiologyOrder::query()->create([
                'hospital_id' => $hospitalId,
                'patient_id' => $patient->id,
                'encounter_id' => $data['encounter_id'] ?? null,
                'ordered_by' => $actor->id,
                'modality' => $data['modality'],
                'study_name' => $data['study_name'],
                'status' => 'ordered',
                'priority' => $data['priority'] ?? 'routine',
                'clinical_indication' => $data['clinical_indication'] ?? null,
                'ordered_at' => now(),
            ]);

            $this->auditLogger->record('radiology.order_created', 'radiology', $order, newValues: $order->toArray(), user: $actor);

            return $this->showOrder($order);
        });
    }

    public function showOrder(RadiologyOrder $order): RadiologyOrder
    {
        return $order->load([
            'patient:id,mrn,first_name,middle_name,last_name,phone',
            'orderedBy:id,first_name,middle_name,last_name',
            'report.reporter:id,first_name,last_name',
            'encounter:id,type,status',
        ]);
    }

    public function saveReport(User $actor, RadiologyOrder $order, array $data): RadiologyReport
    {
        return DB::transaction(function () use ($actor, $order, $data) {
            if (in_array($order->status, ['cancelled'], true)) {
                throw ValidationException::withMessages(['status' => ['Cannot report a cancelled radiology order.']]);
            }

            $report = RadiologyReport::query()->updateOrCreate(
                ['radiology_order_id' => $order->id],
                [
                    'hospital_id' => $order->hospital_id,
                    'reported_by' => $actor->id,
                    'findings' => $data['findings'],
                    'impression' => $data['impression'] ?? null,
                    'status' => 'final',
                    'reported_at' => now(),
                ]
            );

            $order->update(['status' => 'reported']);

            $this->auditLogger->record('radiology.report_saved', 'radiology', $report, newValues: $report->toArray(), user: $actor);

            return $report->load(['reporter:id,first_name,last_name', 'order']);
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

    private function assertEncounter(?string $encounterId, Patient $patient): void
    {
        if ($encounterId === null) {
            return;
        }

        if (! Encounter::query()->whereKey($encounterId)->where('patient_id', $patient->id)->where('hospital_id', $patient->hospital_id)->exists()) {
            throw ValidationException::withMessages(['encounter_id' => ['The selected encounter does not belong to this patient.']]);
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
