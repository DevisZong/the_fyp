<?php

namespace Database\Factories;

use App\Models\Vaccination;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

class VaccinationFactory extends Factory
{
    protected $model = Vaccination::class;

    public function definition()
    {
        return [
            'child_id' => Child::factory(),
            'vaccination_code' => $this->faker->unique()->lexify('VAC???'),
            'vaccination_no' => $this->faker->optional()->numerify('VACNO###'),
            'status' => $this->faker->boolean(30),
        ];
    }
}
