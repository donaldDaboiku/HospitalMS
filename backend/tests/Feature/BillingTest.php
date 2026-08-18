<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Modules\Billing\Models\InsurancePlan;
use App\Modules\Billing\Models\InsuranceProvider;
use App\Modules\Billing\Models\PatientInsurance;
use App\Modules\Patients\Models\Patient;
use Tests\FeatureTestCase;

class BillingTest extends FeatureTestCase
{
    private function makePatient($hospital): Patient
    {
        return Patient::query()->create([
            'hospital_id' => $hospital->id,
            'mrn' => 'MRN-'.strtoupper(substr($hospital->id, 0, 6)),
            'first_name' => 'Ada', 'last_name' => 'Okafor',
            'date_of_birth' => '1990-06-15', 'gender' => 'female',
            'phone' => '08012345678', 'status' => 'active', 'registered_at' => now(),
        ]);
    }

    public function test_accountant_creates_invoice_issues_and_cashier_pays(): void
    {
        $hospital = $this->makeHospital();
        $accountant = $this->makeUser(Roles::ACCOUNTANT, $hospital);
        $cashier = $this->makeUser(Roles::CASHIER, $hospital);
        $patient = $this->makePatient($hospital);

        $invoice = $this->actingAsApi($accountant)->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
            'items' => [
                ['category' => 'consultation', 'description' => 'Doctor consultation', 'quantity' => 1, 'unit_price' => 5000],
                ['category' => 'lab', 'description' => 'FBS test', 'quantity' => 1, 'unit_price' => 2000],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', '7000.00')
            ->json('data');

        $this->actingAsApi($accountant)->postJson('/api/v1/invoices/'.$invoice['id'].'/issue')
            ->assertOk()
            ->assertJsonPath('data.status', 'issued');

        $this->actingAsApi($cashier)->postJson('/api/v1/invoices/'.$invoice['id'].'/payments', [
            'amount' => 3000,
            'method' => 'cash',
        ])->assertOk();

        $this->assertDatabaseHas('invoices', ['id' => $invoice['id'], 'status' => 'partial', 'amount_paid' => '3000.00']);

        $this->actingAsApi($cashier)->postJson('/api/v1/invoices/'.$invoice['id'].'/payments', [
            'amount' => 4000,
            'method' => 'transfer',
            'reference' => 'TRF-12345',
        ])->assertOk();

        $this->assertDatabaseHas('invoices', ['id' => $invoice['id'], 'status' => 'paid', 'amount_paid' => '7000.00']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.payment_received']);
    }

    public function test_cannot_pay_draft_invoice(): void
    {
        $hospital = $this->makeHospital();
        $accountant = $this->makeUser(Roles::ACCOUNTANT, $hospital);
        $cashier = $this->makeUser(Roles::CASHIER, $hospital);
        $patient = $this->makePatient($hospital);

        $invoice = $this->actingAsApi($accountant)->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
            'items' => [['category' => 'consultation', 'description' => 'Visit', 'quantity' => 1, 'unit_price' => 1000]],
        ])->assertCreated()->json('data');

        $this->actingAsApi($cashier)->postJson('/api/v1/invoices/'.$invoice['id'].'/payments', [
            'amount' => 1000,
            'method' => 'cash',
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_insurance_claim_workflow(): void
    {
        $hospital = $this->makeHospital();
        $accountant = $this->makeUser(Roles::ACCOUNTANT, $hospital);
        $patient = $this->makePatient($hospital);

        $provider = InsuranceProvider::query()->create([
            'hospital_id' => $hospital->id, 'name' => 'NHIS', 'is_active' => true,
        ]);
        $plan = InsurancePlan::query()->create([
            'insurance_provider_id' => $provider->id, 'name' => 'Standard', 'coverage_percent' => 80, 'is_active' => true,
        ]);
        $patientInsurance = PatientInsurance::query()->create([
            'hospital_id' => $hospital->id, 'patient_id' => $patient->id,
            'insurance_plan_id' => $plan->id, 'policy_number' => 'POL-001', 'is_active' => true,
        ]);

        $invoice = $this->actingAsApi($accountant)->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
            'items' => [['category' => 'lab', 'description' => 'Blood work', 'quantity' => 1, 'unit_price' => 10000]],
        ])->assertCreated()->json('data');

        $this->actingAsApi($accountant)->postJson('/api/v1/invoices/'.$invoice['id'].'/issue')->assertOk();

        $this->actingAsApi($accountant)->postJson('/api/v1/invoices/'.$invoice['id'].'/claims', [
            'patient_insurance_id' => $patientInsurance->id,
            'claimed_amount' => 8000,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.claimed_amount', '8000.00');

        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance.claim_submitted']);
    }

    public function test_invoices_are_hospital_scoped(): void
    {
        $hospitalA = $this->makeHospital();
        $hospitalB = $this->makeHospital();
        $accountantA = $this->makeUser(Roles::ACCOUNTANT, $hospitalA);
        $accountantB = $this->makeUser(Roles::ACCOUNTANT, $hospitalB);
        $patient = $this->makePatient($hospitalA);

        $invoiceId = $this->actingAsApi($accountantA)->postJson('/api/v1/invoices', [
            'patient_id' => $patient->id,
            'items' => [['category' => 'consultation', 'description' => 'Visit', 'quantity' => 1, 'unit_price' => 5000]],
        ])->assertCreated()->json('data.id');

        $this->actingAsApi($accountantB)->getJson('/api/v1/invoices/'.$invoiceId)->assertNotFound();
    }
}
