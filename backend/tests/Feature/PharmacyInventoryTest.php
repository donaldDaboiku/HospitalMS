<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Modules\Patients\Models\Patient;
use Tests\FeatureTestCase;

class PharmacyInventoryTest extends FeatureTestCase
{
    public function test_doctor_prescribes_and_pharmacist_dispenses_non_expired_stock(): void
    {
        $hospital = $this->makeHospital();
        $doctor = $this->makeUser(Roles::DOCTOR, $hospital);
        $pharmacist = $this->makeUser(Roles::PHARMACIST, $hospital);
        $store = $this->makeUser(Roles::STORE_MANAGER, $hospital);
        $patient = Patient::query()->create(['hospital_id' => $hospital->id, 'mrn' => 'MRN-000001', 'first_name' => 'Ada', 'last_name' => 'Okafor', 'date_of_birth' => '1990-01-01', 'gender' => 'female', 'status' => 'active', 'registered_at' => now()]);

        $product = $this->actingAsApi($store)->postJson('/api/v1/products', ['sku' => 'PARA500', 'name' => 'Paracetamol 500mg', 'unit' => 'tablet'])->assertCreated()->json('data');
        $batch = $this->actingAsApi($store)->postJson('/api/v1/products/'.$product['id'].'/receive', ['batch_number' => 'B-001', 'quantity' => 20, 'expires_at' => now()->addYear()->toDateString()])->assertCreated()->json('data');

        $prescription = $this->actingAsApi($doctor)->postJson('/api/v1/prescriptions', ['patient_id' => $patient->id, 'items' => [['product_id' => $product['id'], 'quantity_prescribed' => 10, 'dose' => '500 mg', 'frequency' => 'TDS']]])->assertCreated()->json('data');
        $itemId = $prescription['items'][0]['id'];

        $this->actingAsApi($pharmacist)->postJson('/api/v1/prescription-items/'.$itemId.'/dispense', ['quantity' => 10])->assertOk()->assertJsonPath('data.status', 'dispensed');
        $this->assertDatabaseHas('stock_batches', ['id' => $batch['id'], 'quantity_available' => 10]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'pharmacy.dispensed']);
    }

    public function test_cannot_dispense_from_expired_batch(): void
    {
        $hospital = $this->makeHospital();
        $doctor = $this->makeUser(Roles::DOCTOR, $hospital);
        $pharmacist = $this->makeUser(Roles::PHARMACIST, $hospital);
        $store = $this->makeUser(Roles::STORE_MANAGER, $hospital);
        $patient = Patient::query()->create(['hospital_id' => $hospital->id, 'mrn' => 'MRN-000002', 'first_name' => 'Chi', 'last_name' => 'Eze', 'date_of_birth' => '1992-02-02', 'gender' => 'female', 'status' => 'active', 'registered_at' => now()]);

        $product = $this->actingAsApi($store)->postJson('/api/v1/products', ['sku' => 'AMOX250', 'name' => 'Amoxicillin 250mg', 'unit' => 'capsule'])->assertCreated()->json('data');
        $this->actingAsApi($store)->postJson('/api/v1/products/'.$product['id'].'/receive', [
            'batch_number' => 'EXPIRED-1',
            'quantity' => 20,
            'expires_at' => now()->addDays(2)->toDateString(),
        ])->assertCreated();

        // Force the batch into the past after receive validation (expires_at must be after today on receive).
        \App\Modules\Pharmacy\Models\StockBatch::query()->where('batch_number', 'EXPIRED-1')->update(['expires_at' => now()->subDay()->toDateString()]);

        $prescription = $this->actingAsApi($doctor)->postJson('/api/v1/prescriptions', [
            'patient_id' => $patient->id,
            'items' => [['product_id' => $product['id'], 'quantity_prescribed' => 5]],
        ])->assertCreated()->json('data');

        $this->actingAsApi($pharmacist)->postJson('/api/v1/prescription-items/'.$prescription['items'][0]['id'].'/dispense', ['quantity' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
}
