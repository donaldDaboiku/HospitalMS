<?php

namespace Database\Seeders;

use App\Modules\Laboratory\Models\LabTest;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Seeder;

class LabTestSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::query()->where('code', 'GH01')->firstOrFail();

        $tests = [
            ['code' => 'FBC', 'name' => 'Full Blood Count', 'category' => 'Haematology', 'specimen_type' => 'Whole blood', 'unit' => null, 'reference_range' => 'See differentials', 'turnaround_hours' => 6],
            ['code' => 'MP', 'name' => 'Malaria Parasite', 'category' => 'Parasitology', 'specimen_type' => 'Whole blood', 'unit' => null, 'reference_range' => 'Negative', 'turnaround_hours' => 2],
            ['code' => 'FBS', 'name' => 'Fasting Blood Sugar', 'category' => 'Chemistry', 'specimen_type' => 'Serum', 'unit' => 'mmol/L', 'reference_range' => '3.9-5.6', 'turnaround_hours' => 4],
            ['code' => 'UE', 'name' => 'Urea & Electrolytes', 'category' => 'Chemistry', 'specimen_type' => 'Serum', 'unit' => null, 'reference_range' => 'See analytes', 'turnaround_hours' => 6],
            ['code' => 'URINE', 'name' => 'Urinalysis', 'category' => 'Urinalysis', 'specimen_type' => 'Urine', 'unit' => null, 'reference_range' => 'See strip panel', 'turnaround_hours' => 2],
        ];

        foreach ($tests as $test) {
            LabTest::query()->updateOrCreate(
                ['hospital_id' => $hospital->id, 'code' => $test['code']],
                [...$test, 'is_active' => true],
            );
        }
    }
}
