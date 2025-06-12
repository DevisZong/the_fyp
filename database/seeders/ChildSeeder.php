<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Child;
use App\Models\GrowthRecords;
use App\Models\Vaccination;
use Illuminate\Support\Str;

class ChildSeeder extends Seeder
{
    public function run()
    {
        // Generate 10 children with correct user linkage and credentials
        for ($i = 0; $i < 20; $i++) {
            $childData = \Database\Factories\ChildFactory::new()->make()->toArray();
            $user = User::factory()->create([
                'name' => $childData['childName'],
                'username' => $childData['childNo'],
                'password' => bcrypt($childData['fatherName']),
                'remember_token' => Str::random(10),
            ]);
            $user->assignRole('child');
            unset($childData['date_of_birth_formatted']);
            $childData['user_id'] = $user->id;
            $child = Child::create($childData);            // Vaccinations are automatically created by the Child model's booted method
            // Generate appointments for the new child
            \Illuminate\Support\Facades\Artisan::call('appointments:generate');
        }
    }
}
