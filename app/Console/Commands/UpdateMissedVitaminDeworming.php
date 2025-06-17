<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\VitaminAndDeworming;
use Database\Seeders\VitaminAndDewormingSeeder;
use Carbon\Carbon;

class UpdateMissedVitaminDeworming extends Command
{
    protected $signature = 'vitamin-deworming:update-missed';
    protected $description = 'Update vitamin and deworming status to missed for children who have passed the visit window';
    public function handle()
    {
        $this->info('Starting to check for missed vitamin and deworming visits...');

        $vitaminSchedule = VitaminAndDewormingSeeder::getVitaminSchedule();

        Child::chunk(100, function ($children) use ($vitaminSchedule) {
            foreach ($children as $child) {
                $ageInMonths = $child->date_of_birth->diffInMonths(now());

                foreach ($vitaminSchedule as $month => $visitName) {
                    // If child has passed the vitamin month + grace period
                    if ($ageInMonths > $month + 1) {
                        $visit = VitaminAndDeworming::where('child_id', $child->id)
                            ->where('status', 'inasubiri')
                            ->whereRaw('TIMESTAMPDIFF(MONTH, ?, created_at) BETWEEN ? AND ?', [
                                $child->date_of_birth,
                                $month - 1,
                                $month + 1
                            ])
                            ->first();

                        if ($visit) {
                            $visit->update(['status' => 'amekosa']);
                            $this->info("Updated vitamin/deworming status to 'amekosa' for child {$child->childNo}, {$visitName}");
                        }
                    }
                }
            }
        });

        $this->info('Completed updating missed vitamin and deworming visits.');
    }
}
