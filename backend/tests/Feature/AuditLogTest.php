<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use App\Modules\Audit\Models\AuditLog;
use Tests\FeatureTestCase;

class AuditLogTest extends FeatureTestCase
{
    public function test_audit_logs_cannot_be_deleted_via_api(): void
    {
        $admin = $this->makeUser(Roles::SUPER_ADMIN);
        $log = AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'auth.login',
            'module' => 'authentication',
            'created_at' => now(),
        ]);

        $this->actingAsApi($admin)
            ->deleteJson('/api/v1/audit-logs/'.$log->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_user_creation_writes_an_audit_log(): void
    {
        $admin = $this->makeUser(Roles::HOSPITAL_ADMIN);

        $this->actingAsApi($admin)->postJson('/api/v1/users', [
            'first_name' => 'Ifeanyi',
            'last_name' => 'Nwosu',
            'email' => 'ifeanyi.nwosu@hms.local',
            'password' => 'SecurePass!12',
            'password_confirmation' => 'SecurePass!12',
            'roles' => [Roles::CASHIER],
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'module' => 'users',
            'user_id' => $admin->id,
        ]);
    }
}
