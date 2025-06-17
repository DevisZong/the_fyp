<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestNewborn extends Command
{
    protected $signature = 'test:create-newborn';
    protected $description = 'Create a test newborn child to verify vitamin/deworming system';

    public function handle()
    {
        $this->info('Creating a test newborn child...');

        // Create user for the child
        $user = User::create([
            'name' => 'Test Newborn Child',
            'username' => 'TESTNB' . time(),
            'password' => Hash::make('testfather')
        ]);
        $user->assignRole('child');

        // Create newborn child (born today)
        $child = Child::create([
            'user_id' => $user->id,
            'childNo' => 'TESTNB' . time(),
            'childName' => 'Test Newborn Baby',
            'date_of_birth' => now()->subDays(1), // 1 day old
            'gender' => 'female',
            'birthWeight' => 3.2,
            'birthHeight' => 50.0,
            'fatherName' => 'Test Father',
            'motherName' => 'Test Mother',
            'birthFacility' => 'Test Hospital',
            'birthAttendant' => 'Test Nurse',
            'email' => 'test' . time() . '@example.com',
            'phoneNo' => '+255700000000',
            'address' => [
                'street' => 'Test Street',
                'ward' => 'Test Ward',
                'Region' => 'Test Region'
            ],
            'motherAge' => 25
        ]);

        $this->info("Created test newborn: {$child->childName} (ID: {$child->id})");
        $this->info("Birth date: {$child->date_of_birth}");
        $this->info("Age in months: {$child->date_of_birth->diffInMonths(now())}");

        $this->info('Test newborn created successfully! Run "php artisan test:vitamin-deworming-system" to verify.');
    }
}
