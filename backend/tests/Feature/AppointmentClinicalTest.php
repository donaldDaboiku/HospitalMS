<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Modules\Doctors\Models\DoctorProfile;
use App\Modules\Patients\Models\Patient;
use App\Modules\Settings\Models\Department;
use Tests\FeatureTestCase;

class AppointmentClinicalTest extends FeatureTestCase
{
    private function makeDepartment($hospital): Department
    {
        return Department::query()->create([
            'hospital_id' => $hospital->id,
            'name' => 'Outpatient',
            'code' => 'OPD-'.substr($hospital->id, 0, 4),
            'type' => 'clinical',
            'is_active' => true,
        ]);
    }

    private function makeDoctor($hospital): array
    {
        $doctor = $this->makeUser(Roles::DOCTOR, $hospital);
        $department = $this->makeDepartment($hospital);
        DoctorProfile::query()->create([
            'hospital_id' => $hospital->id,
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'specialty' => 'General Practice',
            'is_available' => true,
        ]);

        return [$doctor, $department];
    }

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

    public function test_receptionist_can_book_and_check_in_an_appointment(): void
    {
        $hospital = $this->makeHospital();
        $receptionist = $this->makeUser(Roles::RECEPTIONIST, $hospital);
        [$doctor, $department] = $this->makeDoctor($hospital);
        $patient = $this->makePatient($hospital);

        $booked = $this->actingAsApi($receptionist)->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_user_id' => $doctor->id,
            'department_id' => $department->id,
            'scheduled_at' => now()->addHour()->toIso8601String(),
            'reason' => 'Fever',
        ])->assertCreated()->json('data');

        $this->assertSame('scheduled', $booked['status']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.created']);

        $checkedIn = $this->actingAsApi($receptionist)->postJson('/api/v1/appointments/'.$booked['id'].'/check-in')
            ->assertOk()
            ->json('data');

        $this->assertSame('checked_in', $checkedIn['appointment']['status']);
        $this->assertSame('open', $checkedIn['encounter']['status']);
    }

    public function test_nurse_can_triage_but_receptionist_cannot(): void
    {
        $hospital = $this->makeHospital();
        $receptionist = $this->makeUser(Roles::RECEPTIONIST, $hospital);
        $nurse = $this->makeUser(Roles::NURSE, $hospital);
        [$doctor, $department] = $this->makeDoctor($hospital);
        $patient = $this->makePatient($hospital);

        $appointmentId = $this->actingAsApi($receptionist)->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_user_id' => $doctor->id,
            'department_id' => $department->id,
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->json('data.id');

        $encounterId = $this->actingAsApi($receptionist)->postJson('/api/v1/appointments/'.$appointmentId.'/check-in')
            ->json('data.encounter.id');

        $this->actingAsApi($receptionist)->postJson('/api/v1/encounters/'.$encounterId.'/triage', [
            'temperature_c' => 38.5,
            'priority' => 'URGENT',
            'chief_complaint' => 'High fever',
        ])->assertForbidden();

        $this->actingAsApi($nurse)->postJson('/api/v1/encounters/'.$encounterId.'/triage', [
            'temperature_c' => 38.5,
            'pulse' => 98,
            'weight_kg' => 70,
            'height_cm' => 170,
            'priority' => 'URGENT',
            'chief_complaint' => 'High fever',
        ])->assertOk()
            ->assertJsonPath('data.priority', 'URGENT')
            ->assertJsonPath('data.bmi', 24.22);

        $this->assertDatabaseHas('audit_logs', ['action' => 'triage.saved']);
    }

    public function test_doctor_can_add_clinical_note_and_diagnosis(): void
    {
        $hospital = $this->makeHospital();
        $receptionist = $this->makeUser(Roles::RECEPTIONIST, $hospital);
        [$doctor] = $this->makeDoctor($hospital);
        $patient = $this->makePatient($hospital);

        $encounterId = $this->actingAsApi($receptionist)->postJson('/api/v1/encounters', [
            'patient_id' => $patient->id,
            'doctor_user_id' => $doctor->id,
            'type' => 'OPD',
        ])->assertCreated()->json('data.id');

        $this->actingAsApi($doctor)->postJson('/api/v1/encounters/'.$encounterId.'/notes', [
            'chief_complaint' => 'Fever',
            'assessment' => 'Likely viral illness',
            'treatment_plan' => 'Rest and fluids',
        ])->assertCreated();

        $this->actingAsApi($doctor)->postJson('/api/v1/encounters/'.$encounterId.'/diagnoses', [
            'icd10_code' => 'J06.9',
            'description' => 'Acute upper respiratory infection',
            'type' => 'primary',
        ])->assertCreated();

        $this->actingAsApi($doctor)->postJson('/api/v1/encounters/'.$encounterId.'/close')->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_appointments_are_hospital_scoped(): void
    {
        $hospitalA = $this->makeHospital();
        $hospitalB = $this->makeHospital();
        $receptionistA = $this->makeUser(Roles::RECEPTIONIST, $hospitalA);
        $receptionistB = $this->makeUser(Roles::RECEPTIONIST, $hospitalB);
        [$doctor] = $this->makeDoctor($hospitalA);
        $patient = $this->makePatient($hospitalA);

        $appointmentId = $this->actingAsApi($receptionistA)->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_user_id' => $doctor->id,
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->json('data.id');

        $this->actingAsApi($receptionistB)->getJson('/api/v1/appointments/'.$appointmentId)->assertNotFound();
    }

    public function test_cancel_requires_cancel_permission(): void
    {
        $hospital = $this->makeHospital();
        $receptionist = $this->makeUser(Roles::RECEPTIONIST, $hospital);
        $nurse = $this->makeUser(Roles::NURSE, $hospital);
        [$doctor] = $this->makeDoctor($hospital);
        $patient = $this->makePatient($hospital);

        $appointmentId = $this->actingAsApi($receptionist)->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_user_id' => $doctor->id,
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->json('data.id');

        $this->actingAsApi($nurse)->postJson('/api/v1/appointments/'.$appointmentId.'/cancel')->assertForbidden();
        $this->actingAsApi($receptionist)->postJson('/api/v1/appointments/'.$appointmentId.'/cancel', [
            'cancellation_reason' => 'Patient request',
        ])->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_dashboard_counts_live_appointments_and_waiting_patients(): void
    {
        $hospital = $this->makeHospital();
        $receptionist = $this->makeUser(Roles::RECEPTIONIST, $hospital);
        [$doctor] = $this->makeDoctor($hospital);
        $patient = $this->makePatient($hospital);

        $appointmentId = $this->actingAsApi($receptionist)->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'doctor_user_id' => $doctor->id,
            'scheduled_at' => now()->toIso8601String(),
        ])->json('data.id');

        $this->actingAsApi($receptionist)->postJson('/api/v1/appointments/'.$appointmentId.'/check-in')->assertOk();

        $this->actingAsApi($receptionist)->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.todays_appointments', 1)
            ->assertJsonPath('data.waiting_patients', 1)
            ->assertJsonPath('data.doctors_available', 1);
    }
}
