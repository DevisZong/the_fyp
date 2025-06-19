<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\Appointment;
use Carbon\Carbon;

class TestChildrenEligibility extends Command
{
    protected $signature = 'test:children {action=all : Action to perform (all|ages|appointments|create|stats)}';
    protected $description = 'Test children eligibility for vaccinations and appointments';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'ages':
                $this->testChildAges();
                break;
            case 'appointments':
                $this->testTodaysAppointments();
                break;
            case 'create':
                $this->createTestChildren();
                break;
            case 'stats':
                $this->showStatistics();
                break;
            case 'all':
            default:
                $this->showStatistics();
                $this->testChildAges();
                $this->testTodaysAppointments();
                break;
        }
    }

    private function testChildAges()
    {
        $this->info('=== TESTING CHILD AGES FOR VACCINATION ELIGIBILITY ===');
        $this->newLine();

        $children = Child::limit(10)->get();

        if ($children->count() == 0) {
            $this->error('No children found in database.');
            $this->warn('Please run: php artisan migrate:fresh --seed');
            return;
        }

        $vaccinationSchedule = [
            6 => ['bOPV-1', 'Rota-1', 'DPT-HepB-Hib-1', 'PCV13-1'],
            10 => ['bOPV-2', 'Rota-2', 'DPT-HepB-Hib-2', 'PCV13-2'],
            14 => ['bOPV-3', 'Rota-3', 'DPT-HepB-Hib-3', 'PCV13-3', 'IPV'],
            39 => ['Surua Rubella-1'],
            78 => ['Surua Rubella-2']
        ];

        $vitaminSchedule = [6, 12, 18, 24, 30, 36, 42, 48, 54, 60];

        foreach ($children as $child) {
            $dob = Carbon::parse($child->date_of_birth);
            $ageInWeeks = $dob->diffInWeeks(now());
            $ageInMonths = $dob->diffInMonths(now());

            $this->line("Child: <comment>{$child->childName}</comment>");
            $this->line("  Age: {$ageInWeeks} weeks ({$ageInMonths} months)");
            $this->line("  Born: {$child->date_of_birth->format('Y-m-d')}");
            $this->line("  Location: {$child->address['ward']}, {$child->address['Region']}");

            // Check vaccination eligibility
            foreach ($vaccinationSchedule as $weekAge => $vaccines) {
                if ($ageInWeeks == $weekAge) {
                    $this->line("  <bg=green>✅ ELIGIBLE FOR VACCINES:</bg=green> " . implode(', ', $vaccines));
                }
            }

            // Check vitamin eligibility
            foreach ($vitaminSchedule as $monthAge) {
                if ($ageInMonths == $monthAge) {
                    $visitNumber = array_search($monthAge, $vitaminSchedule) + 1;
                    $this->line("  <bg=blue>✅ ELIGIBLE FOR VITAMINS:</bg=blue> Visit {$visitNumber}");
                }
            }

            $this->newLine();
        }
    }

    private function testTodaysAppointments()
    {
        $this->info('=== TODAY\'S APPOINTMENTS ===');
        $this->newLine();

        $appointments = Appointment::with('child')
            ->whereDate('appointment_date', now()->format('Y-m-d'))
            ->get();

        if ($appointments->count() == 0) {
            $this->warn('No appointments scheduled for today.');
            return;
        }

        foreach ($appointments as $appointment) {
            $this->line("📅 <comment>{$appointment->appointment_name}</comment>");
            $this->line("   Child: {$appointment->child->childName}");
            $this->line("   Type: {$appointment->appointment_type}");
            $this->line("   Time: {$appointment->appointment_time}");
            $this->newLine();
        }
    }

    private function createTestChildren()
    {
        $this->info('=== CREATING TEST CHILDREN ===');
        $this->newLine();

        try {
            // Create a 6-week-old child (eligible for first vaccines)
            $this->line('Creating 6-week-old child...');
            $child1 = Child::factory()->create([
                'date_of_birth' => now()->subWeeks(6)->toDate()
            ]);
            $this->line("<bg=green>✅ Created:</bg=green> {$child1->childName} (6 weeks old)");

            // Create a 10-week-old child (eligible for second vaccines)
            $this->line('Creating 10-week-old child...');
            $child2 = Child::factory()->create([
                'date_of_birth' => now()->subWeeks(10)->toDate()
            ]);
            $this->line("<bg=green>✅ Created:</bg=green> {$child2->childName} (10 weeks old)");

            // Create a 6-month-old child (eligible for vitamins)
            $this->line('Creating 6-month-old child...');
            $child3 = Child::factory()->create([
                'date_of_birth' => now()->subMonths(6)->toDate()
            ]);
            $this->line("<bg=green>✅ Created:</bg=green> {$child3->childName} (6 months old)");

            $this->newLine();
            $this->info('Test children created successfully!');

        } catch (\Exception $e) {
            $this->error('Error creating children: ' . $e->getMessage());
        }
    }

    private function showStatistics()
    {
        $this->info('=== DATABASE STATISTICS ===');
        $this->newLine();

        $totalChildren = Child::count();
        $todaysAppointments = Appointment::whereDate('appointment_date', now()->format('Y-m-d'))->count();

        $this->line("Total Children: <comment>{$totalChildren}</comment>");
        $this->line("Today's Appointments: <comment>{$todaysAppointments}</comment>");
        $this->line("Current Date: <comment>" . now()->format('Y-m-d H:i:s') . "</comment>");
        $this->newLine();
    }
}
