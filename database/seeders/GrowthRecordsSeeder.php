<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GrowthRecords;
use App\Models\Child;
use App\Models\HealthCareProvider;

class GrowthRecordsSeeder extends Seeder
{
    public function run()
    {
        Child::all()->each(function ($child) {
            // Optionally assign a random health care provider
            $provider = HealthCareProvider::inRandomOrder()->first();
            GrowthRecords::factory()->count(10)->create([
                'child_id' => $child->id,
                'health_care_provider_id' => $provider ? $provider->id : null,
            ]);
        });
    }
}
