<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\Vaccination;
use Carbon\Carbon;

class UpdateMissedVaccinations extends Command
{
    protected $signature = 'vaccinations:update-missed';
    protected $description = 'Update vaccination status to missed for children who have passed the vaccination window';

    protected $vaccinationSchedule = [
        0 => ['BCG', 'bOPVO'],
        6 => ['bOPV-1', 'Rota-1', 'DPT-HepB-Hib-1', 'PCV13-1'],
        10 => ['bOPV-2', 'Rota-2', 'DPT-HepB-Hib-2', 'PCV13-2'],
        14 => ['bOPV-3', 'Rota-3', 'DPT-HepB-Hib-3', 'PCV13-3', 'IPV'],
        39 => ['Surua Rubella-1'],
        78 => ['Surua Rubella-3']
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting to check for missed vaccinations...');

        // Get all children
        Child::chunk(100, function ($children) {
            foreach ($children as $child) {
                $ageInWeeks = $child->date_of_birth->diffInWeeks(now());

                foreach ($this->vaccinationSchedule as $week => $vaccines) {
                    // If child has passed the vaccination week
                    if ($ageInWeeks > $week + 1) { // Adding 1 week grace period
                        foreach ($vaccines as $vaccine) {
                            $vaccination = Vaccination::where('child_id', $child->id)
                                ->where('vaccination_code', $vaccine)
                                ->where('Hali', 'inasubiri')
                                ->first();

                            if ($vaccination) {
                                $vaccination->update(['Hali' => 'amekosa']);
                                $this->info("Updated vaccination status to 'amekosa' for child {$child->childNo}, vaccine {$vaccine}");
                            }
                        }
                    }
                }
            }
        });

        $this->info('Completed updating missed vaccinations.');
    }
}
