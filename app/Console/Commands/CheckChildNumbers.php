<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;

class CheckChildNumbers extends Command
{
    protected $signature = 'children:check-numbers';
    protected $description = 'Check the generated child numbers';

    public function handle()
    {
        $this->info('Generated Child Numbers:');
        $this->info('========================');

        $children = Child::orderBy('date_of_birth')->get(['childNo', 'childName', 'date_of_birth']);

        foreach ($children as $child) {
            $this->line($child->childNo . ' - ' . $child->childName . ' - Born: ' . $child->date_of_birth->format('Y-m-d'));
        }

        $this->info('');
        $this->info('Total children: ' . $children->count());

        // Group by year to show the sequential numbering
        $this->info('');
        $this->info('Children by Year:');
        $this->info('=================');

        $childrenByYear = $children->groupBy(function ($child) {
            return $child->date_of_birth->year;
        });

        foreach ($childrenByYear as $year => $yearChildren) {
            $this->info("Year $year: " . $yearChildren->count() . ' children');
            foreach ($yearChildren as $child) {
                $this->line('  ' . $child->childNo . ' - ' . $child->childName);
            }
        }
    }
}
