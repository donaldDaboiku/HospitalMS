<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Modules\Laboratory\Models\LabTest;
use App\Modules\Patients\Models\Patient;
use Tests\FeatureTestCase;

class LabRadiologyTest extends FeatureTestCase
{
    private function makePatient($hospital): Patient
    {
        return Patient::query()->create([
            'hospital_id' => $hospital->id,
            'mrn' => 'MRN-'.strtoupper(substr($hospital->id, 0, 6)),
            'first_name' => 'Ada',
            'last_name' => 'Okafor',
            'date_of_birth' => '1990-06-15',
            'gender' => 'female',
            'phone' => '08012345678',
            'status' => 'active',
            'registered_at' => now(),
        ]);
    }

    private function makeTest($hospital, string $code = 'FBS'): LabTest
    {
        return LabTest::query()->create([
            'hospital_id' => $hospital->id,
            'code' => $code,
            'name' => 'Fasting Blood Sugar',
            'category' => 'Chemistry',
            'specimen_type' => 'Serum',
            'unit' => 'mmol/L',
            'reference_range' => '3.9-5.6',
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_order_lab_and_technician_can_collect_result_and_verify(): void
    {
        $hospital = $this->makeHospital();
        $doctor = $this->makeUser(Roles::DOCTOR, $hospital);
        $tech = $this->makeUser(Roles::LAB_TECHNICIAN, $hospital);
        $patient = $this->makePatient($hospital);
        $test = $this->makeTest($hospital);

        $order = $this->actingAsApi($doctor)->postJson('/api/v1/lab/orders', [
            'patient_id' => $patient->id,
            'lab_test_ids' => [$test->id],
            'priority' => 'urgent',
            'clinical_notes' => 'Rule out diabetes',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'ordered')
            ->json('data');

        $this->assertDatabaseHas('audit_logs', ['action' => 'lab.order_created']);

        $this->actingAsApi($tech)->postJson('/api/v1/lab/orders/'.$order['id'].'/collect', [
            'specimen_type' => 'Serum',
        ])->assertOk()->assertJsonPath('data.status', 'collected');

        $itemId = $order['items'][0]['id'];
        $result = $this->actingAsApi($tech)->postJson('/api/v1/lab/order-items/'.$itemId.'/results', [
            'value' => '6.2',
            'flag' => 'high',
        ])->assertOk()
            ->assertJsonPath('data.status', 'preliminary')
            ->json('data');

        $this->actingAsApi($doctor)->postJson('/api/v1/lab/results/'.$result['id'].'/verify')
            ->assertOk()
            ->assertJsonPath('data.status', 'final');

        $this->actingAsApi($doctor)->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.pending_lab_results', 0);
    }

    public function test_receptionist_cannot_order_lab(): void
    {
        $hospital = $this->makeHospital();
        $receptionist = $this->makeUser(Roles::RECEPTIONIST, $hospital);
        $patient = $this->makePatient($hospital);
        $test = $this->makeTest($hospital);

        $this->actingAsApi($receptionist)->postJson('/api/v1/lab/orders', [
            'patient_id' => $patient->id,
            'lab_test_ids' => [$test->id],
        ])->assertForbidden();
    }

    public function test_lab_orders_are_hospital_scoped(): void
    {
        $hospitalA = $this->makeHospital();
        $hospitalB = $this->makeHospital();
        $doctorA = $this->makeUser(Roles::DOCTOR, $hospitalA);
        $doctorB = $this->makeUser(Roles::DOCTOR, $hospitalB);
        $patient = $this->makePatient($hospitalA);
        $test = $this->makeTest($hospitalA);

        $orderId = $this->actingAsApi($doctorA)->postJson('/api/v1/lab/orders', [
            'patient_id' => $patient->id,
            'lab_test_ids' => [$test->id],
        ])->json('data.id');

        $this->actingAsApi($doctorB)->getJson('/api/v1/lab/orders/'.$orderId)->assertNotFound();
    }

    public function test_radiologist_can_report_imaging_order(): void
    {
        $hospital = $this->makeHospital();
        $doctor = $this->makeUser(Roles::DOCTOR, $hospital);
        $radiologist = $this->makeUser(Roles::RADIOLOGIST, $hospital);
        $patient = $this->makePatient($hospital);

        $orderId = $this->actingAsApi($doctor)->postJson('/api/v1/radiology/orders', [
            'patient_id' => $patient->id,
            'modality' => 'XRAY',
            'study_name' => 'Chest PA',
            'clinical_indication' => 'Cough',
        ])->assertCreated()->json('data.id');

        $this->actingAsApi($radiologist)->postJson('/api/v1/radiology/orders/'.$orderId.'/report', [
            'findings' => 'Clear lung fields.',
            'impression' => 'No acute cardiopulmonary process.',
        ])->assertOk();

        $this->actingAsApi($radiologist)->getJson('/api/v1/radiology/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.status', 'reported')
            ->assertJsonPath('data.report.impression', 'No acute cardiopulmonary process.');
    }

    public function test_verified_lab_result_cannot_be_overwritten(): void
    {
        $hospital = $this->makeHospital();
        $doctor = $this->makeUser(Roles::DOCTOR, $hospital);
        $tech = $this->makeUser(Roles::LAB_TECHNICIAN, $hospital);
        $patient = $this->makePatient($hospital);
        $test = $this->makeTest($hospital);

        $order = $this->actingAsApi($doctor)->postJson('/api/v1/lab/orders', [
            'patient_id' => $patient->id,
            'lab_test_ids' => [$test->id],
        ])->assertCreated()->json('data');

        $this->actingAsApi($tech)->postJson('/api/v1/lab/orders/'.$order['id'].'/collect', [
            'specimen_type' => 'Serum',
        ])->assertOk();

        $itemId = $order['items'][0]['id'];
        $result = $this->actingAsApi($tech)->postJson('/api/v1/lab/order-items/'.$itemId.'/results', [
            'value' => '6.2',
            'flag' => 'high',
        ])->assertOk()->json('data');

        $this->actingAsApi($doctor)->postJson('/api/v1/lab/results/'.$result['id'].'/verify')
            ->assertOk()
            ->assertJsonPath('data.status', 'final');

        $this->actingAsApi($tech)->postJson('/api/v1/lab/order-items/'.$itemId.'/results', [
            'value' => '4.1',
            'flag' => 'normal',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('lab_results', [
            'id' => $result['id'],
            'value' => '6.2',
            'status' => 'final',
        ]);
        $this->assertDatabaseHas('lab_orders', [
            'id' => $order['id'],
            'status' => 'completed',
        ]);
    }

    public function test_reported_radiology_order_cannot_be_overwritten(): void
    {
        $hospital = $this->makeHospital();
        $doctor = $this->makeUser(Roles::DOCTOR, $hospital);
        $radiologist = $this->makeUser(Roles::RADIOLOGIST, $hospital);
        $patient = $this->makePatient($hospital);

        $orderId = $this->actingAsApi($doctor)->postJson('/api/v1/radiology/orders', [
            'patient_id' => $patient->id,
            'modality' => 'XRAY',
            'study_name' => 'Chest PA',
            'clinical_indication' => 'Cough',
        ])->assertCreated()->json('data.id');

        $this->actingAsApi($radiologist)->postJson('/api/v1/radiology/orders/'.$orderId.'/report', [
            'findings' => 'Clear lung fields.',
            'impression' => 'No acute cardiopulmonary process.',
        ])->assertOk();

        $this->actingAsApi($radiologist)->postJson('/api/v1/radiology/orders/'.$orderId.'/report', [
            'findings' => 'Altered findings.',
            'impression' => 'Should not replace final report.',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->actingAsApi($radiologist)->getJson('/api/v1/radiology/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.report.findings', 'Clear lung fields.')
            ->assertJsonPath('data.report.impression', 'No acute cardiopulmonary process.');
    }
}
