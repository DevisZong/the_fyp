<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Child;
use App\Models\HealthCareProvider;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create users for children if not already set
        Child::all()->each(function ($child) {
            if (!$child->user_id) {
                $user = User::factory()->create([
                    'name' => $child->childName,
                    'username' => $child->childNo,
                    // Password same as ChildController@store: father's name
                    'password' => bcrypt($child->fatherName),
                    'remember_token' => Str::random(10),
                ]);
                $user->assignRole('child');
                $child->user_id = $user->id;
                $child->save();
            }
        });

        // Create users for healthcare providers if not already set
        HealthCareProvider::all()->each(function ($hcp) {
            if (!$hcp->user_id) {
                $user = User::factory()->create([
                    'name' => $hcp->name,
                    'username' => $hcp->license,
                    'password' => bcrypt($hcp->facility . '@' . $hcp->license),
                    'remember_token' => Str::random(10),
                ]);
                $user->assignRole($hcp->userRole);
                $hcp->user_id = $user->id;
                $hcp->save();
            }
        });
    }
}
