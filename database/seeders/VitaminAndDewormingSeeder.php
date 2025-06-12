<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Child;
use App\Models\VitaminAndDeworming;
use Carbon\Carbon;

class VitaminAndDewormingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Child::all()->each(function (Child $child) {
            $dateOfBirth = Carbon::parse($child->date_of_birth);
            $now = Carbon::now();

            for ($i = 0; $i < 10; $i++) {
                // Schedule visit 6 months after birth and then every 6 months
                $scheduledDate = $dateOfBirth->copy()->addMonths(6 * ($i + 1));

                // For past scheduled dates
                if ($scheduledDate->lt($now)) {
                    // 70% chance of having received the vitamins
                    $received = rand(1, 100) <= 70;

                    VitaminAndDeworming::create([
                        'child_id' => $child->id,
                        'Vitamin_A' => $received,
                        'Deworming' => $received,
                        'status' => $received ? 'imekamilika' : 'amekosa',
                        'created_at' => $scheduledDate,
                        'updated_at' => $scheduledDate
                    ]);
                } else {
                    // Future visits are always inasubiri with false values
                    VitaminAndDeworming::create([
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
