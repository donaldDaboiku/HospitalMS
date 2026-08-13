<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\Department;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::query()->where('code', 'GH01')->firstOrFail();

        $departments = [
            ['name' => 'Outpatient', 'code' => 'OPD', 'type' => 'clinical'],
            ['name' => 'Inpatient', 'code' => 'IPD', 'type' => 'clinical'],
            ['name' => 'Emergency', 'code' => 'ER', 'type' => 'clinical'],
            ['name' => 'Laboratory', 'code' => 'LAB', 'type' => 'diagnostics'],
            ['name' => 'Radiology', 'code' => 'RAD', 'type' => 'diagnostics'],
            ['name' => 'Pharmacy', 'code' => 'PHARM', 'type' => 'clinical'],
            ['name' => 'Theatre', 'code' => 'OT', 'type' => 'clinical'],
            ['name' => 'Accounts', 'code' => 'ACC', 'type' => 'administrative'],
        ];

        foreach ($departments as $department) {
            Department::query()->firstOrCreate(
                [
                    'hospital_id' => $hospital->id,
                    'code' => $department['code'],
                ],
                [
                    'name' => $department['name'],
                    'type' => $department['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
