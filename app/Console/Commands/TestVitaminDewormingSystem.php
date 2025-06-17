<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\VitaminAndDeworming;

class TestVitaminDewormingSystem extends Command
{
    protected $signature = 'test:vitamin-deworming-system';
    protected $description = 'Test the vitamin and deworming automatic system';
    public function handle()
    {
        $this->info('Testing Vitamin and Deworming System...');

        // Get the latest child
        $child = Child::latest()->first();

        if (!$child) {
            $this->error('No children found in the system.');
            return;
        }

        $this->info("Testing with child: {$child->childName} (ID: {$child->id})");
        $this->info("Child birth date: {$child->date_of_birth}");
        $ageInMonths = $child->date_of_birth->diffInMonths(now());
        $this->info("Child age in months: {$ageInMonths}");

        // Check vitamin and deworming records
        $vitaminRecords = VitaminAndDeworming::where('child_id', $child->id)->orderBy('created_at')->get();

        $this->info("Found {$vitaminRecords->count()} vitamin/deworming records:");

        foreach ($vitaminRecords as $record) {
            $scheduledDate = $record->created_at->format('Y-m-d');
            $monthsFromBirth = $child->date_of_birth->diffInMonths($record->created_at);
            $isFuture = $record->created_at->gt(now());
            $isPast = $record->created_at->lt(now());

            $this->info("  - Visit {$monthsFromBirth} months ({$scheduledDate}): Status = {$record->status}, Vitamin A = " .
                ($record->Vitamin_A ? 'Yes' : 'No') . ", Deworming = " . ($record->Deworming ? 'Yes' : 'No') .
                " [" . ($isFuture ? 'FUTURE' : ($isPast ? 'PAST' : 'CURRENT')) . "]");
        }

        // Show expected results for a newborn
        if ($ageInMonths <= 1) {
            $this->info("\nFor a newborn child, all visits should be 'inasubiri' status with Vitamin A = No, Deworming = No");
        }

        $this->info('System test completed!');
    }
}
