<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Models\User;
use Tests\FeatureTestCase;

class UserManagementTest extends FeatureTestCase
{
    public function test_hospital_admin_can_create_a_user(): void
    {
        $hospital = $this->makeHospital();
        $admin = $this->makeUser(Roles::HOSPITAL_ADMIN, $hospital);

        $response = $this->actingAsApi($admin)->postJson('/api/v1/users', [
            'first_name' => 'Ngozi',
            'last_name' => 'Okafor',
            'email' => 'ngozi.okafor@hms.local',
            'phone' => '08011112222',
            'password' => 'SecurePass!12',
            'password_confirmation' => 'SecurePass!12',
            'roles' => [Roles::NURSE],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'ngozi.okafor@hms.local')
            ->assertJsonPath('data.hospital_id', $hospital->id);

        $this->assertDatabaseHas('users', ['email' => 'ngozi.okafor@hms.local']);
        $this->assertTrue(User::query()->where('email', 'ngozi.okafor@hms.local')->first()->hasRole(Roles::NURSE));
    }

    public function test_hospital_admin_cannot_assign_super_admin_role(): void
    {
        $admin = $this->makeUser(Roles::HOSPITAL_ADMIN);

        $this->actingAsApi($admin)->postJson('/api/v1/users', [
            'first_name' => 'Root',
            'last_name' => 'User',
            'email' => 'root@hms.local',
            'password' => 'SecurePass!12',
            'password_confirmation' => 'SecurePass!12',
            'roles' => [Roles::SUPER_ADMIN],
        ])->assertStatus(422);
    }

    public function test_hospital_admin_only_lists_users_in_their_hospital(): void
    {
        $hospitalA = $this->makeHospital();
        $hospitalB = $this->makeHospital();
        $admin = $this->makeUser(Roles::HOSPITAL_ADMIN, $hospitalA);
        $this->makeUser(Roles::NURSE, $hospitalA);
        $this->makeUser(Roles::NURSE, $hospitalB);

        $response = $this->actingAsApi($admin)->getJson('/api/v1/users');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('hospital_id')->unique()->all();
        $this->assertSame([$hospitalA->id], $ids);
    }
}
