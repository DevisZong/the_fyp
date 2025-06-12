<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vaccination;

class CheckVaccinationStatus extends Command
{
    protected $signature = 'check:vaccination-status';
    protected $description = 'Check distribution of vaccination statuses';

    public function handle()
    {
        $statuses = Vaccination::select('Hali')
            ->selectRaw('count(*) as total')
            ->groupBy('Hali')
            ->get();

        $this->info("Vaccination Status Distribution:");
        foreach ($statuses as $status) {
            $this->info("{$status->Hali}: {$status->total}");
        }
    }
}
