<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Doctors\Models\DoctorProfile;
use App\Modules\Doctors\Models\DoctorSchedule;
use App\Modules\Settings\Models\Department;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::query()->where('code', 'GH01')->firstOrFail();
        $doctor = User::query()->where('email', 'doctor@hms.local')->first();
        $department = Department::query()->where('hospital_id', $hospital->id)->where('code', 'OPD')->first();

        if ($doctor === null) {
            return;
        }

        DoctorProfile::query()->updateOrCreate(
            ['user_id' => $doctor->id],
            [
                'hospital_id' => $hospital->id,
                'department_id' => $department?->id,
                'specialty' => 'General Practice',
                'license_number' => 'MDCN-0001',
                'is_available' => true,
            ]
        );

        foreach ([1, 2, 3, 4, 5] as $day) {
            DoctorSchedule::query()->firstOrCreate(
                [
                    'hospital_id' => $hospital->id,
                    'doctor_user_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                ],
                [
                    'department_id' => $department?->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
