<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GrowthRecords;
use App\Models\Child;
use App\Models\HealthCareProvider;
use Faker\Factory;

class GrowthRecordsSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function run()
    {
        Child::all()->each(function ($child) {
            // Skip creating initial record as it's now automatically created with birth measurements

            // Create additional growth records
            $numRecords = random_int(5, 10); // Random number of records
            $provider = HealthCareProvider::inRandomOrder()->first();

            // Generate dates between birth and now, sorted
            $recordDates = collect(range(1, $numRecords))
                ->map(function () use ($child) {
                    return $this->faker->dateTimeBetween($child->date_of_birth, 'now');
                })
                ->sort()
                ->values();

            // Create records with age-appropriate measurements
            $recordDates->each(function ($date) use ($child, $provider) {
                GrowthRecords::factory()
                    ->forChild($child, $date)
                    ->create([
                        'child_id' => $child->id,
                        'health_care_provider_id' => $provider ? $provider->id : null,
                    ]);
            });
        });
    }
}
