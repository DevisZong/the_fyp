<?php

namespace Database\Factories;

use App\Models\Child;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChildFactory extends Factory
{
    protected $model = Child::class;
    public function definition()
    {        // Create a distribution of ages that will ensure we get all vaccination statuses
        $ageGroups = [
            'newborn' => ['start' => now()->subWeeks(4), 'end' => now(), 'weight' => 20],
            'middle_age' => ['start' => now()->subWeeks(40), 'end' => now()->subWeeks(4), 'weight' => 40],
            'older' => ['start' => now()->subWeeks(80), 'end' => now()->subWeeks(40), 'weight' => 40]
        ];

        $roll = $this->faker->numberBetween(1, 100);
        $dateRange = null;
        $sum = 0;

        foreach ($ageGroups as $group) {
            $sum += $group['weight'];
            if ($roll <= $sum) {
                $dateRange = ['start' => $group['start'], 'end' => $group['end']];
                break;
            }
        }

        return [
            'user_id' => \App\Models\User::factory(),
            'childNo' => $this->faker->unique()->numerify('CHILD####'),
            'childName' => $this->faker->name(),
            'date_of_birth' => $this->faker->dateTimeBetween($dateRange['start'], $dateRange['end']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'birthWeight' => $this->faker->randomFloat(2, 2.0, 5.0),
            'birthHeight' => $this->faker->randomFloat(2, 40.0, 60.0),
            'fatherName' => $this->faker->name('male'),
            'motherName' => $this->faker->name('female'),
            'birthFacility' => $this->faker->company(),
            'birthAttendant' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phoneNo' => $this->faker->randomElement(['255674960366', '255766418267', '255620840216']),
            'address' => [
                'street' => $this->faker->streetName(),
                'ward' => $this->faker->streetSuffix(),
                'Region' => $this->faker->state(),
            ],
            'motherAge' => $this->faker->numberBetween(18, 45),
        ];
    }
    public function configure()
    {
        return $this->afterCreating(function (Child $child) {
            $dateOfBirth = Carbon::parse($child->date_of_birth);
            $now = Carbon::now();

            // Create all 10 visits
            for ($i = 0; $i < 10; $i++) {
                $scheduledDate = $dateOfBirth->copy()->addMonths(6 * ($i + 1));

                if ($scheduledDate->lt($now)) {
                    // For past scheduled dates
                    // 70% chance of completed, 30% chance of missed
                    $received = $this->faker->boolean(70);
                    $status = $received ? 'imekamilika' : 'amekosa';

                    \App\Models\VitaminAndDeworming::create([
                        'child_id' => $child->id,
                        'Vitamin_A' => $received,
                        'Deworming' => $received,
                        'status' => $status,
                        'created_at' => $scheduledDate,
                        'updated_at' => $scheduledDate
                    ]);
                } else {
                    // For future scheduled dates
                    \App\Models\VitaminAndDeworming::create([
                        'child_id' => $child->id,
                        'Vitamin_A' => false,
                        'Deworming' => false,
                        'status' => 'inasubiri',
                        'created_at' => $scheduledDate,
                        'updated_at' => $scheduledDate
                    ]);
                }
            }
        });
    }
}
