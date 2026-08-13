<?php

namespace Database\Factories;

use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Hospital>
 */
class HospitalFactory extends Factory
{
    protected $model = Hospital::class;

    public function definition(): array
    {
        $name = fake()->company().' Hospital';

        return [
            'name' => $name,
            'code' => strtoupper(Str::random(6)),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('080########'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => 'Lagos',
            'country' => 'NG',
            'settings' => [],
            'is_active' => true,
        ];
    }
}
