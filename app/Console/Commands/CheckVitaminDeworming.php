<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\VitaminAndDeworming;

class CheckVitaminDeworming extends Command
{
    protected $signature = 'children:check-vitamin-deworming';
    protected $description = 'Check vitamin and deworming records generation';

    public function handle()
    {
        $this->info('Vitamin and Deworming Records Analysis:');
        $this->info('=========================================');
        
        $child = Child::first();
        if (!$child) {
            $this->error('No children found in database');
            return;
        }
        
        $this->info('Child: ' . $child->childName . ' (Born: ' . $child->date_of_birth->format('Y-m-d') . ')');
        $this->info('Child current age: ' . $child->date_of_birth->diffInMonths(now()) . ' months');
        $this->info('');
        
        $records = VitaminAndDeworming::where('child_id', $child->id)
            ->orderBy('created_at')
            ->get();
            
        $this->info('Total vitamin/deworming records: ' . $records->count());
        $this->info('Expected: 10 records (every 6 months until age 5)');
        $this->info('');
        
        foreach ($records as $i => $record) {
            $ageAtVisit = $child->date_of_birth->diffInMonths($record->created_at);
            $expectedAge = ($i + 1) * 6; // 6, 12, 18, 24, ... months
            
            $this->line(sprintf(
                '%2d. Scheduled: %s - Age: %2d months (Expected: %2d months) - Status: %s - Vitamin A: %s - Deworming: %s',
                $i + 1,
                $record->created_at->format('Y-m-d'),
                $ageAtVisit,
                $expectedAge,
                $record->status,
                $record->Vitamin_A ? 'Yes' : 'No',
                $record->Deworming ? 'Yes' : 'No'
            ));
        }
        
        $this->info('');
        $this->info('Analysis:');
        $this->info('=========');
        
        // Check if records follow the 6-month pattern
        $correctPattern = true;
        foreach ($records as $i => $record) {
            $ageAtVisit = $child->date_of_birth->diffInMonths($record->created_at);
            $expectedAge = ($i + 1) * 6;
            
            if ($ageAtVisit !== $expectedAge) {
                $correctPattern = false;
                $this->error("Record " . ($i + 1) . " is at age $ageAtVisit months, expected $expectedAge months");
            }
        }
        
        if ($correctPattern && $records->count() === 10) {
            $this->info('✅ Records follow correct 6-month pattern');
        } elseif ($records->count() !== 10) {
            $this->error('❌ Expected 10 records, found ' . $records->count());
        }
        
        // Check age range (should be 6 months to 60 months)
        $firstRecord = $records->first();
        $lastRecord = $records->last();
          if ($firstRecord) {
            $firstAge = round($child->date_of_birth->diffInMonths($firstRecord->created_at));
            $lastAge = round($child->date_of_birth->diffInMonths($lastRecord->created_at));
            
            $this->info("Age range: $firstAge to $lastAge months");
              if ($firstAge === 6 && $lastAge === 60) {
                $this->info('✅ Correct age range (6 to 60 months / 5 years)');
            } else {
                $this->error('❌ Expected age range 6 to 60 months, got ' . $firstAge . ' to ' . $lastAge . ' months');
            }
        }
    }
}
