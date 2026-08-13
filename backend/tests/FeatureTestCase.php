<?php

namespace Tests;

use App\Core\Support\Roles;
use App\Models\User;
use App\Modules\Settings\Models\Hospital;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeHospital(): Hospital
    {
        return Hospital::factory()->create();
    }

    protected function makeUser(string $role, ?Hospital $hospital = null): User
    {
        $user = User::factory()->create([
            'hospital_id' => $role === Roles::SUPER_ADMIN ? null : ($hospital?->id ?? $this->makeHospital()->id),
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    protected function actingAsApi(User $user): static
    {
        Sanctum::actingAs($user);

        return $this;
    }
}
