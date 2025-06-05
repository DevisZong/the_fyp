<?php

namespace Database\Factories;

use App\Models\HealthCareProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HealthCareProviderFactory extends Factory
{
    protected $model = HealthCareProvider::class;
    public function definition()
    {
        $name = $this->faker->company();
        $license = $this->faker->unique()->bothify('LIC-####');
        $facility = $this->faker->companySuffix();
        $userRole = $this->faker->randomElement(['doctor', 'nurse']);

        // Create user first
        $user = User::factory()->create([
            'name' => $name,
            'username' => $license,
            'password' => bcrypt($facility . '@' . $license),
            'remember_token' => $this->faker->regexify('[A-Za-z0-9]{10}')
        ]);

        // Assign role
        $user->assignRole($userRole);

        return [
            'name' => $name,
            'license' => $license,
            'userRole' => $userRole,
            'facility' => $facility,
            'contact' => $this->faker->unique()->numerify('07########'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'picture' => $this->faker->randomElement(['doctor1.jpg', 'doctor2.jpg', 'nurse1.jpg', 'nurse2.jpg']),
            'status' => $this->faker->boolean(),
            'user_id' => $user->id,
        ];
    }
}
