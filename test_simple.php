<?php

// Simple Database Test Script
// Run with: php test_simple.php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Child;
use App\Models\Appointment;
use Carbon\Carbon;

function testChildAges() {
    echo "=== TESTING CHILD AGES FOR VACCINATION ELIGIBILITY ===\n\n";
    
    $children = Child::limit(10)->get(); // Get first 10 children
    
    if ($children->count() == 0) {
        echo "No children found in database. Please run: php artisan migrate:fresh --seed\n";
        return;
    }
    
    foreach ($children as $child) {
        $dob = Carbon::parse($child->date_of_birth);
        $ageInWeeks = $dob->diffInWeeks(now());
        $ageInMonths = $dob->diffInMonths(now());
        
        echo "Child: {$child->childName}\n";
        echo "  Age: {$ageInWeeks} weeks ({$ageInMonths} months)\n";
        echo "  Born: {$child->date_of_birth->format('Y-m-d')}\n";
        
        // Check vaccination eligibility
        $vaccinationSchedule = [
            6 => ['bOPV-1', 'Rota-1', 'DPT-HepB-Hib-1', 'PCV13-1'],
            10 => ['bOPV-2', 'Rota-2', 'DPT-HepB-Hib-2', 'PCV13-2'],
            14 => ['bOPV-3', 'Rota-3', 'DPT-HepB-Hib-3', 'PCV13-3', 'IPV'],
            39 => ['Surua Rubella-1'],
            78 => ['Surua Rubella-2']
        ];
        
        foreach ($vaccinationSchedule as $weekAge => $vaccines) {
            if ($ageInWeeks == $weekAge) {
                echo "  ✅ ELIGIBLE FOR VACCINES: " . implode(', ', $vaccines) . "\n";
            }
        }
        
        // Check vitamin eligibility
        $vitaminSchedule = [6, 12, 18, 24, 30, 36, 42, 48, 54, 60];
        foreach ($vitaminSchedule as $monthAge) {
            if ($ageInMonths == $monthAge) {
                $visitNumber = array_search($monthAge, $vitaminSchedule) + 1;
                echo "  ✅ ELIGIBLE FOR VITAMINS: Visit {$visitNumber}\n";
            }
        }
        
        echo "\n";
    }
}

function testTodaysAppointments() {
    echo "=== TODAY'S APPOINTMENTS ===\n\n";
    
    $appointments = Appointment::with('child')
        ->whereDate('appointment_date', now()->format('Y-m-d'))
        ->get();
    
    if ($appointments->count() == 0) {
        echo "No appointments scheduled for today.\n";
        return;
    }
    
    foreach ($appointments as $appointment) {
        echo "📅 {$appointment->appointment_name}\n";
        echo "   Child: {$appointment->child->childName}\n";
        echo "   Type: {$appointment->appointment_type}\n";
        echo "   Time: {$appointment->appointment_time}\n\n";
    }
}

function createTestChildren() {
    echo "=== CREATING TEST CHILDREN ===\n\n";
    
    try {
        // Create a 6-week-old child (eligible for first vaccines)
        echo "Creating 6-week-old child...\n";
        $child1 = Child::factory()->create([
            'date_of_birth' => now()->subWeeks(6)->toDate()
        ]);
        echo "✅ Created: {$child1->childName} (6 weeks old)\n";
        
        // Create a 10-week-old child (eligible for second vaccines)
        echo "Creating 10-week-old child...\n";
        $child2 = Child::factory()->create([
            'date_of_birth' => now()->subWeeks(10)->toDate()
        ]);
        echo "✅ Created: {$child2->childName} (10 weeks old)\n";
        
        // Create a 6-month-old child (eligible for vitamins)
        echo "Creating 6-month-old child...\n";
        $child3 = Child::factory()->create([
            'date_of_birth' => now()->subMonths(6)->toDate()
        ]);
        echo "✅ Created: {$child3->childName} (6 months old)\n";
        
        echo "\nTest children created successfully!\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error creating children: " . $e->getMessage() . "\n\n";
    }
}

function showStatistics() {
    echo "=== DATABASE STATISTICS ===\n\n";
    
    $totalChildren = Child::count();
    $todaysAppointments = Appointment::whereDate('appointment_date', now()->format('Y-m-d'))->count();
    
    echo "Total Children: {$totalChildren}\n";
    echo "Today's Appointments: {$todaysAppointments}\n";
    echo "Current Date: " . now()->format('Y-m-d H:i:s') . "\n\n";
}

// Run all tests
echo "🔬 STARTING DATABASE TESTS...\n";
echo "=============================\n\n";

showStatistics();
testChildAges();
testTodaysAppointments();

echo "Would you like to create test children? (y/n): ";
// For automated testing, uncomment the line below:
// createTestChildren();

echo "✅ TESTS COMPLETED!\n";
