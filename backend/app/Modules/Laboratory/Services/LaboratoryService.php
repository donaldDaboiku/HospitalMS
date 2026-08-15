<?php

namespace App\Modules\Laboratory\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Laboratory\Models\LabOrder;
use App\Modules\Laboratory\Models\LabOrderItem;
use App\Modules\Laboratory\Models\LabResult;
use App\Modules\Laboratory\Models\LabSpecimen;
use App\Modules\Laboratory\Models\LabTest;
use App\Modules\Patients\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LaboratoryService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function listTests(User $actor, array $filters = []): Collection
    {
        $query = LabTest::query()->orderBy('name');
        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);

        if (! array_key_exists('include_inactive', $filters) || ! filter_var($filters['include_inactive'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function upsertTest(User $actor, array $data, ?LabTest $test = null): LabTest
    {
        $hospitalId = $actor->isSuperAdmin()
            ? ($data['hospital_id'] ?? $test?->hospital_id)
            : $actor->hospital_id;
        abort_if($hospitalId === null, 422, 'A hospital is required.');

        $payload = [
            ...Arr::only($data, ['code', 'name', 'category', 'specimen_type', 'unit', 'reference_range', 'turnaround_hours', 'is_active']),
            'hospital_id' => $hospitalId,
            'code' => strtoupper($data['code']),
        ];

        if ($test) {
            abort_unless($actor->isSuperAdmin() || $test->hospital_id === $actor->hospital_id, 403);
            $test->fill($payload)->save();
        } else {
            $test = LabTest::query()->create($payload);
        }

        $this->auditLogger->record('lab.test_saved', 'laboratory', $test, newValues: $test->toArray(), user: $actor);

        return $test;
    }

    public function paginateOrders(User $actor, array $filters): LengthAwarePaginator
    {
        $query = LabOrder::query()->with([
            'patient:id,mrn,first_name,middle_name,last_name',
            'orderedBy:id,first_name,middle_name,last_name',
            'items.test',
            'specimen',
        ]);
        $this->scopeHospital($query, $actor, $filters['hospital_id'] ?? null);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        return $query->latest('ordered_at')->paginate(
            min((int) ($filters['per_page'] ?? config('hms.pagination.per_page')), config('hms.pagination.max_per_page'))
        );
    }

    public function createOrder(User $actor, array $data): LabOrder
    {
        return DB::transaction(function () use ($actor, $data) {
            $hospitalId = $this->resolveHospitalId($actor, $data);
            $patient = $this->resolvePatient($data['patient_id'], $hospitalId);
            $this->assertEncounter($data['encounter_id'] ?? null, $patient);

            $tests = LabTest::query()
                ->where('hospital_id', $hospitalId)
                ->where('is_active', true)
                ->whereIn('id', $data['lab_test_ids'])
                ->get();

            if ($tests->count() !== count(array_unique($data['lab_test_ids']))) {
                throw ValidationException::withMessages([
                    'lab_test_ids' => ['One or more selected lab tests are invalid for this hospital.'],
                ]);
            }

            $order = LabOrder::query()->create([
                'hospital_id' => $hospitalId,
                'patient_id' => $patient->id,
                'encounter_id' => $data['encounter_id'] ?? null,
                'ordered_by' => $actor->id,
                'status' => 'ordered',
                'priority' => $data['priority'] ?? 'routine',
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'ordered_at' => now(),
            ]);

            foreach ($tests as $test) {
                $order->items()->create([
                    'lab_test_id' => $test->id,
                    'status' => 'ordered',
                ]);
            }

            $this->auditLogger->record('lab.order_created', 'laboratory', $order, newValues: $order->toArray(), user: $actor);

            return $this->showOrder($order);
        });
    }

    public function showOrder(LabOrder $order): LabOrder
    {
        return $order->load([
            'patient:id,mrn,first_name,middle_name,last_name,phone',
            'orderedBy:id,first_name,middle_name,last_name',
            'items.test',
            'items.result.enteredBy:id,first_name,last_name',
            'items.result.verifiedBy:id,first_name,last_name',
            'specimen.collector:id,first_name,last_name',
            'encounter:id,type,status',
        ]);
    }

    public function collectSpecimen(User $actor, LabOrder $order, array $data): LabOrder
    {
        return DB::transaction(function () use ($actor, $order, $data) {
            if (! in_array($order->status, ['ordered'], true)) {
                throw ValidationException::withMessages(['status' => ['Only ordered labs can be collected.']]);
            }

            LabSpecimen::query()->updateOrCreate(
                ['lab_order_id' => $order->id],
                [
                    'hospital_id' => $order->hospital_id,
                    'patient_id' => $order->patient_id,
                    'collected_by' => $actor->id,
                    'specimen_type' => $data['specimen_type'],
                    'status' => 'collected',
                    'collected_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ]
            );

            $order->update(['status' => 'collected']);
            $order->items()->where('status', 'ordered')->update(['status' => 'collected']);

            $this->auditLogger->record('lab.specimen_collected', 'laboratory', $order, newValues: ['status' => 'collected'], user: $actor);

            return $this->showOrder($order->fresh());
        });
    }

    public function enterResult(User $actor, LabOrderItem $item, array $data): LabResult
    {
        return DB::transaction(function () use ($actor, $item, $data) {
            $order = $item->order;
            if (! in_array($order->status, ['collected', 'in_progress', 'completed'], true)) {
                throw ValidationException::withMessages(['status' => ['Collect the specimen before entering results.']]);
            }

            $result = LabResult::query()->updateOrCreate(
                ['lab_order_item_id' => $item->id],
                [
                    'hospital_id' => $order->hospital_id,
                    'patient_id' => $order->patient_id,
                    'entered_by' => $actor->id,
                    'value' => $data['value'],
                    'unit' => $data['unit'] ?? $item->test?->unit,
                    'flag' => $data['flag'] ?? 'normal',
                    'status' => 'preliminary',
                    'notes' => $data['notes'] ?? null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]
            );

            $item->update(['status' => 'resulted']);
            $order->update(['status' => 'in_progress']);

            $this->auditLogger->record('lab.result_entered', 'laboratory', $result, newValues: $result->toArray(), user: $actor);

            return $result->load(['item.test', 'enteredBy:id,first_name,last_name']);
        });
    }

    public function verifyResult(User $actor, LabResult $result): LabResult
    {
        return DB::transaction(function () use ($actor, $result) {
            if ($result->status === 'final') {
                throw ValidationException::withMessages(['status' => ['Result is already verified.']]);
            }

            $result->fill([
                'status' => 'final',
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ])->save();

            $item = $result->item;
            $item->update(['status' => 'verified']);

            $order = $item->order()->with('items')->first();
            if ($order && $order->items->every(fn (LabOrderItem $row) => in_array($row->status, ['verified', 'cancelled'], true))) {
                $order->update(['status' => 'completed']);
            }

            $this->auditLogger->record('lab.result_verified', 'laboratory', $result, newValues: $result->fresh()->toArray(), user: $actor);

            return $result->fresh(['item.test', 'enteredBy:id,first_name,last_name', 'verifiedBy:id,first_name,last_name']);
        });
    }

    public function pendingResultsCount(User $actor): int
    {
        $query = LabOrder::query()->whereIn('status', ['ordered', 'collected', 'in_progress']);
        $this->scopeHospital($query, $actor, null);

        return $query->count();
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
