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

    /**
     * Create vitamin and deworming records for a specific child
     */
    public static function createVitaminDewormingRecords($childId)
    {
        $child = Child::find($childId);
        if (!$child) return;

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
    }

    /**
     * Get the vitamin and deworming schedule (in months)
     */
    public static function getVitaminSchedule()
    {
        return [
            6 => 'Visit 1 (6 months)',
            12 => 'Visit 2 (12 months)',
            18 => 'Visit 3 (18 months)',
            24 => 'Visit 4 (24 months)',
            30 => 'Visit 5 (30 months)',
            36 => 'Visit 6 (36 months)',
            42 => 'Visit 7 (42 months)',
            48 => 'Visit 8 (48 months)',
            54 => 'Visit 9 (54 months)',
            60 => 'Visit 10 (60 months)'
        ];
    }
}
