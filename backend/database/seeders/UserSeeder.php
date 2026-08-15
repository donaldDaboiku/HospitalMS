<?php

namespace Database\Seeders;

use App\Core\Support\Roles;
use App\Models\User;
use App\Modules\Settings\Models\Branch;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::query()->where('code', 'GH01')->firstOrFail();
        $branch = Branch::query()->where('hospital_id', $hospital->id)->where('code', 'MAIN')->first();
        $password = config('hms.seed.admin_password') ?: 'ChangeMe!Hms2026';

        $superAdmin = User::query()->firstOrCreate(
            ['email' => config('hms.seed.admin_email')],
            [
                'hospital_id' => null,
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles([Roles::SUPER_ADMIN]);

        $staff = [
            ['email' => 'admin@hms.local', 'first_name' => 'Hospital', 'last_name' => 'Admin', 'role' => Roles::HOSPITAL_ADMIN],
            ['email' => 'doctor@hms.local', 'first_name' => 'Ada', 'last_name' => 'Okeke', 'role' => Roles::DOCTOR],
            ['email' => 'nurse@hms.local', 'first_name' => 'Chioma', 'last_name' => 'Eze', 'role' => Roles::NURSE],
            ['email' => 'reception@hms.local', 'first_name' => 'Tunde', 'last_name' => 'Balogun', 'role' => Roles::RECEPTIONIST],
            ['email' => 'lab@hms.local', 'first_name' => 'Ifeanyi', 'last_name' => 'Nwosu', 'role' => Roles::LAB_TECHNICIAN],
            ['email' => 'radiology@hms.local', 'first_name' => 'Ngozi', 'last_name' => 'Adeyemi', 'role' => Roles::RADIOLOGIST],
            ['email' => 'pharmacy@hms.local', 'first_name' => 'Bola', 'last_name' => 'Adebayo', 'role' => Roles::PHARMACIST],
            ['email' => 'store@hms.local', 'first_name' => 'Musa', 'last_name' => 'Ibrahim', 'role' => Roles::STORE_MANAGER],
        ];

        foreach ($staff as $row) {
            $user = User::query()->firstOrCreate(
                ['email' => $row['email']],
                [
                    'hospital_id' => $hospital->id,
                    'branch_id' => $branch?->id,
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$row['role']]);
        }
    }
}
