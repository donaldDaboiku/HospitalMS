<?php

namespace App\Modules\Patients\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientService
{
    private const RELATIONS = ['contacts', 'allergies', 'medicalHistories', 'identifications'];

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Patient::query()->with(self::RELATIONS);
        $this->scopeToHospital($query, $actor, $filters['hospital_id'] ?? null);

        if (! empty($filters['search'])) {
            $value = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $phone = $this->normalizePhone($filters['search']);
            $query->where(function (Builder $builder) use ($value, $operator, $phone) {
                $builder->where('mrn', $operator, $value)
                    ->orWhere('first_name', $operator, $value)
                    ->orWhere('middle_name', $operator, $value)
                    ->orWhere('last_name', $operator, $value)
                    ->orWhere('phone', $operator, $value);

                if ($phone !== null) {
                    $builder->orWhere('phone', $phone);
                }
            });
        }

        if (! empty($filters['date_of_birth'])) {
            $query->whereDate('date_of_birth', $filters['date_of_birth']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('last_name')->orderBy('first_name')->paginate(
            min((int) ($filters['per_page'] ?? config('hms.pagination.per_page')), config('hms.pagination.max_per_page')),
        );
    }

    public function findPotentialDuplicates(User $actor, array $attributes): array
    {
        $query = Patient::query();
        $this->scopeToHospital($query, $actor, $attributes['hospital_id'] ?? null);

        $query->where('first_name', $attributes['first_name'])
            ->where('last_name', $attributes['last_name'])
            ->whereDate('date_of_birth', $attributes['date_of_birth']);

        if (! empty($attributes['phone'])) {
            $phone = $this->normalizePhone($attributes['phone']);
            $query->orWhere(function (Builder $builder) use ($actor, $attributes, $phone) {
                $this->scopeToHospital($builder, $actor, $attributes['hospital_id'] ?? null);
                $builder->where('phone', $phone ?? $attributes['phone']);
            });
        }

        foreach ($attributes['identifications'] ?? [] as $identification) {
            $query->orWhere(function (Builder $builder) use ($actor, $attributes, $identification) {
                $this->scopeToHospital($builder, $actor, $attributes['hospital_id'] ?? null);
                $builder->whereHas('identifications', fn (Builder $identifications) => $identifications
                    ->where('type', $identification['type'])
                    ->where('number', $identification['number']));
            });
        }

        return $query->limit(10)->get()->all();
    }

    public function create(User $actor, array $data): Patient
    {
        return DB::transaction(function () use ($actor, $data) {
            $hospitalId = $actor->isSuperAdmin()
                ? $data['hospital_id'] ?? null
                : $actor->hospital_id;

            abort_if($hospitalId === null, 422, 'A hospital is required to register a patient.');
            $this->assertBranchBelongsToHospital($data['branch_id'] ?? null, $hospitalId);

            $patient = Patient::query()->create([
                ...Arr::except($data, ['contacts', 'allergies', 'medical_histories', 'identifications', 'hospital_id', 'mrn']),
                'hospital_id' => $hospitalId,
                'mrn' => $this->nextMrn($hospitalId),
                'phone' => $this->normalizePhone($data['phone'] ?? null),
                'country' => strtoupper($data['country'] ?? 'NG'),
                'registered_at' => now(),
            ]);

            $this->replaceClinicalIdentityData($patient, $data);
            $patient->load(self::RELATIONS);

            $this->auditLogger->record(
                action: 'patient.created',
                module: 'patients',
                auditable: $patient,
                newValues: $this->auditSnapshot($patient),
                user: $actor,
            );

            return $patient;
        });
    }

    public function update(User $actor, Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($actor, $patient, $data) {
            $patient->load(self::RELATIONS);
            $old = $this->auditSnapshot($patient);
            $this->assertBranchBelongsToHospital($data['branch_id'] ?? $patient->branch_id, $patient->hospital_id);
            $payload = Arr::except($data, ['contacts', 'allergies', 'medical_histories', 'identifications', 'hospital_id', 'mrn']);
            if (array_key_exists('phone', $payload)) {
                $payload['phone'] = $this->normalizePhone($payload['phone']);
            }
            $patient->fill($payload)->save();
            $this->replaceClinicalIdentityData($patient, $data);
            $patient->load(self::RELATIONS);

            $this->auditLogger->record(
                action: 'patient.updated',
                module: 'patients',
                auditable: $patient,
                oldValues: $old,
                newValues: $this->auditSnapshot($patient),
                user: $actor,
            );

            return $patient;
        });
    }

    private function nextMrn(string $hospitalId): string
    {
        // lockForUpdate cannot lock a missing row, so concurrent first registrations
        // can both observe null. Ensure the sequence exists first, then lock it.
        DB::table('patient_mrn_sequences')->insertOrIgnore([
            'hospital_id' => $hospitalId,
            'next_value' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('patient_mrn_sequences')
            ->where('hospital_id', $hospitalId)
            ->lockForUpdate()
            ->first();

        $number = (int) $sequence->next_value;

        DB::table('patient_mrn_sequences')->where('hospital_id', $hospitalId)->update([
            'next_value' => $number + 1,
            'updated_at' => now(),
        ]);

        return sprintf('MRN-%06d', $number);
    }

    private function replaceClinicalIdentityData(Patient $patient, array $data): void
    {
        foreach (['contacts', 'allergies', 'medical_histories', 'identifications'] as $relation) {
            if (! array_key_exists($relation, $data)) {
                continue;
            }

            $eloquentRelation = match ($relation) {
                'medical_histories' => $patient->medicalHistories(),
                default => $patient->{$relation}(),
            };
            $eloquentRelation->delete();
            $eloquentRelation->createMany($data[$relation]);
        }
    }

    private function scopeToHospital(Builder $query, User $actor, ?string $requestedHospitalId): void
    {
        if (! $actor->isSuperAdmin()) {
            $query->where('hospital_id', $actor->hospital_id);
        } elseif ($requestedHospitalId !== null) {
            $query->where('hospital_id', $requestedHospitalId);
        }
    }

    private function assertBranchBelongsToHospital(?string $branchId, string $hospitalId): void
    {
        if ($branchId === null) {
            return;
        }

        if (! Branch::query()->whereKey($branchId)->where('hospital_id', $hospitalId)->exists()) {
            throw ValidationException::withMessages([
                'branch_id' => ['The selected branch does not belong to this hospital.'],
            ]);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits === '' ? null : $digits;
    }

    private function auditSnapshot(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'hospital_id' => $patient->hospital_id,
            'mrn' => $patient->mrn,
            'name' => $patient->name,
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'gender' => $patient->gender,
            'status' => $patient->status,
            'contacts' => $patient->contacts->map->only(['type', 'full_name', 'relationship', 'phone', 'is_primary'])->all(),
            'allergies' => $patient->allergies->map->only(['allergen', 'reaction', 'severity'])->all(),
            'medical_histories' => $patient->medicalHistories->map->only(['condition_name', 'status'])->all(),
        ];
    }
}
