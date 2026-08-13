<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\Branch;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::query()->firstOrCreate(
            ['code' => 'GH01'],
            [
                'name' => 'General Hospital',
                'slug' => 'general-hospital',
                'email' => 'info@hms.local',
                'phone' => '08000000000',
                'address' => '1 Hospital Road',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'NG',
                'settings' => [],
                'is_active' => true,
            ]
        );

        Branch::query()->firstOrCreate(
            [
                'hospital_id' => $hospital->id,
                'code' => 'MAIN',
            ],
            [
                'name' => 'Main Campus',
                'address' => $hospital->address,
                'phone' => $hospital->phone,
                'is_active' => true,
            ]
        );
    }
}
