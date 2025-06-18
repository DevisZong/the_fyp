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
        // Generate 20 children with correct user linkage and credentials
        for ($i = 0; $i < 20; $i++) {
            $childData = \Database\Factories\ChildFactory::new()->make()->toArray();

            // Generate the child number based on the date of birth
            $childNo = Child::generateChildNumber($childData['date_of_birth']);

            $user = User::factory()->create([
                'name' => $childData['childName'],
                'username' => $childNo,
                'password' => bcrypt($childData['fatherName']),
                'remember_token' => Str::random(10),
            ]);
            $user->assignRole('child');
            unset($childData['date_of_birth_formatted']);
            $childData['user_id'] = $user->id;
            $childData['childNo'] = $childNo; // Set the generated childNo
            $child = Child::create($childData);            // Vaccinations are automatically created by the Child model's booted method
            // Generate appointments for the new child
            \Illuminate\Support\Facades\Artisan::call('appointments:generate');
        }
    }
}
