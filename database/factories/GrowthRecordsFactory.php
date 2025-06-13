<?php

namespace Database\Factories;

use App\Models\GrowthRecords;
use App\Models\Child;
use App\Models\HealthCareProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrowthRecordsFactory extends Factory
{
    protected $model = GrowthRecords::class;
    public function definition()
    {
        return [
            'weight' => null, // Will be set during creation based on child's age
            'height' => null, // Will be set during creation based on child's age
            'created_at' => null, // Will be set during creation based on child's birth date
            'updated_at' => now(),
            'health_care_provider_id' => HealthCareProvider::inRandomOrder()->first() ?? HealthCareProvider::factory(),
        ];
    }

    public function forChild(Child $child, $recordDate = null)
    {
        return $this->state(function (array $attributes) use ($child, $recordDate) {
            $date = $recordDate ?? $this->faker->dateTimeBetween($child->date_of_birth, 'now');
            $ageInMonths = $child->date_of_birth->diffInMonths($date);

            // Age-appropriate weight ranges (in kg) based on WHO growth standards
            // These are approximate ranges, can be refined further
            $weightRange = match (true) {
                $ageInMonths === 0 => [2.5, 4.5],  // newborn
                $ageInMonths <= 3 => [3.5, 7.0],   // 0-3 months
                $ageInMonths <= 6 => [5.0, 9.0],   // 3-6 months
                $ageInMonths <= 12 => [7.0, 12.0], // 6-12 months
                $ageInMonths <= 24 => [9.0, 15.0], // 12-24 months
                default => [11.0, 20.0],           // 2+ years
            };

            // Age-appropriate height ranges (in cm) based on WHO growth standards
            $heightRange = match (true) {
                $ageInMonths === 0 => [45.0, 55.0],  // newborn
                $ageInMonths <= 3 => [50.0, 65.0],   // 0-3 months
                $ageInMonths <= 6 => [60.0, 72.0],   // 3-6 months
                $ageInMonths <= 12 => [67.0, 82.0],  // 6-12 months
                $ageInMonths <= 24 => [75.0, 95.0],  // 12-24 months
                default => [85.0, 120.0],            // 2+ years
            };

            return [
                'weight' => $this->faker->randomFloat(2, $weightRange[0], $weightRange[1]),
                'height' => $this->faker->randomFloat(2, $heightRange[0], $heightRange[1]),
                'created_at' => $date,
            ];
        });
    }
}
