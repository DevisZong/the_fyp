<?php

namespace Database\Factories;

use App\Models\Vaccination;
use App\Models\Child;
use App\Models\HealthCareProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class VaccinationFactory extends Factory
{
    protected $model = Vaccination::class;

    public function definition()
    {
        $status = $this->faker->boolean(30);
        return [
            'child_id' => Child::factory(),
            'vaccination_code' => $this->faker->unique()->lexify('VAC???'),
            'vaccination_no' => null,
            'Hali' => 'inasubiri',
            'health_care_provider_id' => null,
        ];
    }
}
