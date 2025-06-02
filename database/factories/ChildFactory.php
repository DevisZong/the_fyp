<?php

namespace Database\Factories;

use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition()
    {
        return [
            'childNo' => $this->faker->unique()->numerify('CHILD####'),
            'childName' => $this->faker->name(),
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'birthWeight' => $this->faker->randomFloat(2, 2.0, 5.0),
            'birthHeight' => $this->faker->randomFloat(2, 40.0, 60.0),
            'fatherName' => $this->faker->name('male'),
            'motherName' => $this->faker->name('female'),
            'birthFacility' => $this->faker->company(),
            'birthAttendant' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phoneNo' => '255674960366',
            'address' => [
                'street' => $this->faker->streetName(),
                'ward' => $this->faker->streetSuffix(),
                'Region' => $this->faker->state(),
            ],
            'motherAge' => $this->faker->numberBetween(18, 45),
        ];
    }
}
