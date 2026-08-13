<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use Tests\FeatureTestCase;

class AuthorizationTest extends FeatureTestCase
{
    public function test_super_admin_can_access_users_and_audit_logs(): void
    {
        $admin = $this->makeUser(Roles::SUPER_ADMIN);

        $this->actingAsApi($admin)->getJson('/api/v1/users')->assertOk();
        $this->actingAsApi($admin)->getJson('/api/v1/audit-logs')->assertOk();
        $this->actingAsApi($admin)->getJson('/api/v1/dashboard/summary')->assertOk();
    }

    public function test_doctor_cannot_list_users(): void
    {
        $doctor = $this->makeUser(Roles::DOCTOR);

        $this->actingAsApi($doctor)
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_patient_cannot_access_staff_dashboard(): void
    {
        $patient = $this->makeUser(Roles::PATIENT);

        $this->actingAsApi($patient)
            ->getJson('/api/v1/dashboard/summary')
            ->assertForbidden();
    }

    public function test_hospital_admin_cannot_view_users_from_another_hospital(): void
    {
        $hospitalA = $this->makeHospital();
        $hospitalB = $this->makeHospital();
        $admin = $this->makeUser(Roles::HOSPITAL_ADMIN, $hospitalA);
        $other = $this->makeUser(Roles::NURSE, $hospitalB);

        $this->actingAsApi($admin)
            ->getJson('/api/v1/users/'.$other->id)
            ->assertForbidden();
    }

    public function test_receptionist_can_view_dashboard_but_not_audit_logs(): void
    {
        $receptionist = $this->makeUser(Roles::RECEPTIONIST);

        $this->actingAsApi($receptionist)->getJson('/api/v1/dashboard/summary')->assertOk();
        $this->actingAsApi($receptionist)->getJson('/api/v1/audit-logs')->assertForbidden();
    }
}
