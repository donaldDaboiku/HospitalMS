<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Modules\Settings\Models\Branch;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

class PatientManagementTest extends FeatureTestCase
{
    private function payload(array $overrides = []): array
    {
        return [
            'first_name' => 'Ada',
            'last_name' => 'Okafor',
            'date_of_birth' => '1990-06-15',
            'gender' => 'female',
            'phone' => '08012345678',
            'email' => 'ada.okafor@example.test',
            'blood_group' => 'O+',
            'genotype' => 'AA',
            'contacts' => [[
                'type' => 'emergency',
                'full_name' => 'Chinedu Okafor',
                'relationship' => 'Spouse',
                'phone' => '08087654321',
                'is_primary' => true,
            ]],
            'allergies' => [[
                'allergen' => 'Penicillin',
                'reaction' => 'Rash',
                'severity' => 'moderate',
            ]],
            'medical_histories' => [[
                'condition_name' => 'Asthma',
                'status' => 'active',
            ]],
            'identifications' => [[
                'type' => 'NIN',
                'number' => '12345678901',
            ]],
            ...$overrides,
        ];
    }

    public function test_receptionist_can_register_patient_with_an_mrn_and_identity_data(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);

        $response = $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.mrn', 'MRN-000001')
            ->assertJsonPath('data.hospital_id', $receptionist->hospital_id)
            ->assertJsonCount(1, 'data.contacts')
            ->assertJsonCount(1, 'data.allergies');

        $this->assertDatabaseHas('patients', ['hospital_id' => $receptionist->hospital_id, 'mrn' => 'MRN-000001']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.created', 'module' => 'patients']);
    }

    public function test_mrn_sequence_is_unique_per_hospital(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);

        $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())->assertCreated();
        $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload([
            'first_name' => 'Bola',
            'email' => 'bola@example.test',
            'phone' => '08022223333',
            'identifications' => [],
        ]))->assertJsonPath('data.mrn', 'MRN-000002');
    }

    public function test_mrn_allocation_recovers_when_sequence_row_already_exists(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);

        DB::table('patient_mrn_sequences')->insert([
            'hospital_id' => $receptionist->hospital_id,
            'next_value' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.mrn', 'MRN-000001');

        $this->assertDatabaseHas('patient_mrn_sequences', [
            'hospital_id' => $receptionist->hospital_id,
            'next_value' => 2,
        ]);
    }

    public function test_duplicate_check_finds_name_birth_date_and_phone_matches(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);
        $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())->assertCreated();

        $this->actingAsApi($receptionist)->postJson('/api/v1/patients/duplicates', $this->payload())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mrn', 'MRN-000001');
    }

    public function test_patient_search_is_hospital_scoped(): void
    {
        $firstReceptionist = $this->makeUser(Roles::RECEPTIONIST);
        $secondReceptionist = $this->makeUser(Roles::RECEPTIONIST);

        $this->actingAsApi($firstReceptionist)->postJson('/api/v1/patients', $this->payload())->assertCreated();
        $this->actingAsApi($secondReceptionist)->getJson('/api/v1/patients?search=Ada')->assertJsonCount(0, 'data');
    }

    public function test_nurse_can_view_but_cannot_register_or_edit_a_patient(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);
        $patientId = $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())->json('data.id');
        $nurse = $this->makeUser(Roles::NURSE, $receptionist->hospital);

        $this->actingAsApi($nurse)->getJson('/api/v1/patients/'.$patientId)->assertOk();
        $this->actingAsApi($nurse)->postJson('/api/v1/patients', $this->payload(['email' => 'different@example.test']))->assertForbidden();
        $this->actingAsApi($nurse)->putJson('/api/v1/patients/'.$patientId, ['phone' => '08000000000'])->assertForbidden();
    }

    public function test_patient_view_is_audited(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);
        $patientId = $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())->json('data.id');

        $this->actingAsApi($receptionist)->getJson('/api/v1/patients/'.$patientId)->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.viewed', 'auditable_id' => $patientId]);
    }

    public function test_patient_cannot_be_registered_into_another_hospitals_branch(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);
        $otherHospital = $this->makeHospital();
        $otherBranch = Branch::query()->create([
            'hospital_id' => $otherHospital->id,
            'name' => 'Other Campus',
            'code' => 'OTHER',
            'is_active' => true,
        ]);

        $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload([
            'branch_id' => $otherBranch->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('branch_id');
    }

    public function test_client_supplied_mrn_is_ignored(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);

        $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload([
            'mrn' => 'MRN-999999',
        ]))->assertCreated()->assertJsonPath('data.mrn', 'MRN-000001');
    }

    public function test_formatted_phone_numbers_still_match_as_duplicates(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);
        $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())->assertCreated();

        $this->actingAsApi($receptionist)->postJson('/api/v1/patients/duplicates', $this->payload([
            'first_name' => 'Different',
            'last_name' => 'Person',
            'date_of_birth' => '1985-01-01',
            'phone' => '080 1234 5678',
        ]))->assertOk()->assertJsonPath('data.0.mrn', 'MRN-000001');
    }

    public function test_patient_from_another_hospital_is_not_found(): void
    {
        $firstReceptionist = $this->makeUser(Roles::RECEPTIONIST);
        $secondReceptionist = $this->makeUser(Roles::RECEPTIONIST);
        $patientId = $this->actingAsApi($firstReceptionist)->postJson('/api/v1/patients', $this->payload())->json('data.id');

        $this->actingAsApi($secondReceptionist)->getJson('/api/v1/patients/'.$patientId)->assertNotFound();
    }

    public function test_super_admin_patient_audit_uses_the_patient_hospital(): void
    {
        $hospital = $this->makeHospital();
        $admin = $this->makeUser(Roles::SUPER_ADMIN);

        $patientId = $this->actingAsApi($admin)->postJson('/api/v1/patients', $this->payload([
            'hospital_id' => $hospital->id,
        ]))->assertCreated()->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'patient.created',
            'auditable_id' => $patientId,
            'hospital_id' => $hospital->id,
        ]);
    }
}
