<?php

namespace Database\Factories;

use App\Models\GrowthRecords;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrowthRecordsFactory extends Factory
{
    protected $model = GrowthRecords::class;

    public function definition()
    {
        return [
            'child_id' => Child::factory(),
            'weight' => $this->faker->randomFloat(2, 2.0, 20.0),
            'height' => $this->faker->randomFloat(2, 40.0, 120.0),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => now(),
        ];
    }
}
