<?php

namespace Database\Seeders;

use App\Core\Support\PermissionCatalog;
use App\Core\Support\Roles;
use App\Modules\Roles\Models\Permission;
use App\Modules\Roles\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionCatalog::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Roles::all() as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(PermissionCatalog::forRole($roleName));
        }
    }
}
