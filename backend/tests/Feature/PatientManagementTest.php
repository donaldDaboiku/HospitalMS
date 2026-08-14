<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Modules\Settings\Models\Branch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function test_receptionist_can_upload_and_view_patient_photo(): void
    {
        Storage::fake('local');

        $receptionist = $this->makeUser(Roles::RECEPTIONIST);
        $patientId = $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())
            ->assertCreated()
            ->json('data.id');

        $this->actingAsApi($receptionist)
            ->post('/api/v1/patients/'.$patientId.'/photo', [
                'photo' => UploadedFile::fake()->image('ada.jpg', 240, 240),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.photo_url', '/api/v1/patients/'.$patientId.'/photo');

        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.photo_updated']);

        $this->actingAsApi($receptionist)
            ->get('/api/v1/patients/'.$patientId.'/photo')
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_nurse_cannot_upload_patient_photo(): void
    {
        Storage::fake('local');

        $hospital = $this->makeHospital();
        $receptionist = $this->makeUser(Roles::RECEPTIONIST, $hospital);
        $nurse = $this->makeUser(Roles::NURSE, $hospital);
        $patientId = $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload())->json('data.id');

        $this->actingAsApi($nurse)
            ->post('/api/v1/patients/'.$patientId.'/photo', [
                'photo' => UploadedFile::fake()->image('ada.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_contact_can_map_to_an_already_registered_patient(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);

        $relatedId = $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload([
            'first_name' => 'Chinedu',
            'last_name' => 'Okafor',
            'email' => 'chinedu@example.test',
            'phone' => '08087654321',
            'contacts' => [],
            'identifications' => [['type' => 'NIN', 'number' => '10987654321']],
        ]))->assertCreated()->json('data.id');

        $response = $this->actingAsApi($receptionist)->postJson('/api/v1/patients', $this->payload([
            'first_name' => 'Ada',
            'email' => 'ada2@example.test',
            'phone' => '08011112222',
            'identifications' => [],
            'contacts' => [[
                'type' => 'next_of_kin',
                'related_patient_id' => $relatedId,
                'relationship' => 'Spouse',
                'is_primary' => true,
            ]],
        ]))->assertCreated();

        $response->assertJsonPath('data.contacts.0.related_patient_id', $relatedId)
            ->assertJsonPath('data.contacts.0.related_patient.mrn', 'MRN-000001')
            ->assertJsonPath('data.contacts.0.full_name', 'Chinedu Okafor')
            ->assertJsonPath('data.contacts.0.phone', '08087654321');
    }

    public function test_related_patient_must_belong_to_same_hospital(): void
    {
        $receptionistA = $this->makeUser(Roles::RECEPTIONIST);
        $receptionistB = $this->makeUser(Roles::RECEPTIONIST);

        $foreignPatientId = $this->actingAsApi($receptionistB)->postJson('/api/v1/patients', $this->payload([
            'contacts' => [],
            'identifications' => [],
        ]))->json('data.id');

        $this->actingAsApi($receptionistA)->postJson('/api/v1/patients', $this->payload([
            'contacts' => [[
                'type' => 'next_of_kin',
                'related_patient_id' => $foreignPatientId,
                'relationship' => 'Sibling',
            ]],
            'identifications' => [],
        ]))->assertStatus(422);
    }

    public function test_family_registration_creates_linked_members(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);

        $response = $this->actingAsApi($receptionist)->postJson('/api/v1/patients/family', [
            'primary' => [
                'first_name' => 'Ada',
                'last_name' => 'Okafor',
                'date_of_birth' => '1990-06-15',
                'gender' => 'female',
                'phone' => '08012345678',
                'address' => '12 Broad Street',
                'state' => 'Lagos',
                'country' => 'NG',
            ],
            'members' => [
                [
                    'relationship_to_primary' => 'Spouse',
                    'first_name' => 'Chinedu',
                    'last_name' => 'Okafor',
                    'date_of_birth' => '1988-03-10',
                    'gender' => 'male',
                    'phone' => '08087654321',
                ],
                [
                    'relationship_to_primary' => 'Child',
                    'first_name' => 'Amaka',
                    'last_name' => 'Okafor',
                    'date_of_birth' => '2015-01-20',
                    'gender' => 'female',
                ],
            ],
        ])->assertCreated();

        $response->assertJsonPath('data.primary.mrn', 'MRN-000001')
            ->assertJsonCount(2, 'data.members')
            ->assertJsonPath('data.members.0.mrn', 'MRN-000002')
            ->assertJsonPath('data.members.1.mrn', 'MRN-000003')
            ->assertJsonPath('data.primary.contacts.0.related_patient_id', $response->json('data.members.0.id'))
            ->assertJsonPath('data.members.0.contacts.0.related_patient_id', $response->json('data.primary.id'))
            ->assertJsonPath('data.members.0.contacts.0.relationship', 'Spouse')
            ->assertJsonPath('data.primary.contacts.0.relationship', 'Spouse')
            ->assertJsonPath('data.members.1.contacts.0.relationship', 'Child')
            ->assertJsonPath('data.primary.contacts.1.relationship', 'Parent');

        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.family_registered']);
    }
}
