<?php

namespace Database\Factories;

use App\Models\HealthCareProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HealthCareProviderFactory extends Factory
{
    protected $model = HealthCareProvider::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'license' => $this->faker->unique()->bothify('LIC-####'),
            'userRole' => $this->faker->randomElement(['doctor', 'nurse', 'admin']),
            'facility' => $this->faker->companySuffix(),
            'contact' => $this->faker->unique()->numerify('07########'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'status' => $this->faker->boolean(),
            'user_id' => User::factory([
                'name' => $this->faker->name(),
                'username' => $this->faker->unique()->userName(),
                'password' => bcrypt('password'),
                'remember_token' => $this->faker->regexify('[A-Za-z0-9]{10}')
            ]),
        ];
    }
}
