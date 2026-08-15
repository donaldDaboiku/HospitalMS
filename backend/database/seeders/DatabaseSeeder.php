<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HospitalSeeder::class,
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            DoctorSeeder::class,
            LabTestSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
