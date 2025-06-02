<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HealthCareProvider;

class HealthCareProviderSeeder extends Seeder
{
    public function run()
    {
        HealthCareProvider::factory()->count(5)->create();
    }
}
