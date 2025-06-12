<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\VitaminAndDeworming;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class VitaminAndDewormingFactory extends Factory
{
    protected $model = VitaminAndDeworming::class;

    public function definition()
    {
        return [
            'child_id' => Child::factory(),
            'Vitamin_A' => $this->faker->boolean,
            'Deworming' => $this->faker->boolean,
            'status' => $this->faker->randomElement(['inasubiri', 'imekamilika', 'amekosa'])
        ];
    }

    public function withStatus($status)
    {
        return $this->state(function (array $attributes) use ($status) {
            return [
                'status' => $status
            ];
        });
    }
}
