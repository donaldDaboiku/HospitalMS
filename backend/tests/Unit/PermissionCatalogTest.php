<?php

namespace Tests\Unit;

use App\Core\Support\PermissionCatalog;
use App\Core\Support\Roles;
use PHPUnit\Framework\TestCase;

class PermissionCatalogTest extends TestCase
{
    public function test_every_role_is_defined(): void
    {
        $this->assertContains(Roles::SUPER_ADMIN, Roles::all());
        $this->assertContains(Roles::PATIENT, Roles::all());
        $this->assertCount(18, Roles::all());
    }

    public function test_patient_has_no_staff_permissions(): void
    {
        $this->assertSame([], PermissionCatalog::forRole(Roles::PATIENT));
    }

    public function test_super_admin_receives_the_full_catalog(): void
    {
        $this->assertSame(PermissionCatalog::all(), PermissionCatalog::forRole(Roles::SUPER_ADMIN));
        $this->assertContains('patient.view', PermissionCatalog::all());
        $this->assertContains('audit.view', PermissionCatalog::all());
    }
}
