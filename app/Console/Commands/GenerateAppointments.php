<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\Appointment;
use Carbon\Carbon;

class GenerateAppointments extends Command
{
    protected $signature = 'appointments:generate {--child-id= : Generate appointments for a specific child ID}';
    protected $description = 'Generate monthly visit and vaccination appointments for children';

    public function handle()
    {
        $childId = $this->option('child-id');

        if ($childId) {
            // Generate appointments for a specific child
            $child = Child::find($childId);
            if (!$child) {
                $this->error("Child with ID {$childId} not found.");
                return 1;
            }

            Child::generateAppointmentsForChild($child);
            $this->info("Appointments generated successfully for child: {$child->childName}");
        } else {
            // Generate appointments for all children
            $children = Child::all();

            foreach ($children as $child) {
                Child::generateAppointmentsForChild($child);
            }

            $this->info("Appointments generated successfully for all children.");
        }

        return 0;
    }
}
