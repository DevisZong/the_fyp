<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Child;
use App\Models\Appointment;
use Carbon\Carbon;

echo "=== TESTING DATABASE CHILDREN ELIGIBILITY ===\n";
echo "Current Date: " . now()->format('Y-m-d H:i:s') . "\n";
echo "==============================================\n\n";

// Test 1: Get all children and their ages
echo "1. ALL CHILDREN AND THEIR AGES:\n";
echo "-------------------------------\n";

$children = Child::all();
foreach ($children as $child) {
    $dob = Carbon::parse($child->date_of_birth);
    $ageInDays = $dob->diffInDays(now());
    $ageInWeeks = $dob->diffInWeeks(now());
    $ageInMonths = $dob->diffInMonths(now());
    
    echo "Child: {$child->childName} (ID: {$child->id})\n";
    echo "  Birth Date: {$child->date_of_birth->format('Y-m-d')}\n";
    echo "  Age: {$ageInDays} days, {$ageInWeeks} weeks, {$ageInMonths} months\n";
    echo "  Father: {$child->fatherName}\n";
    echo "  Mother: {$child->motherName}\n";
    echo "  Address: {$child->address['street']}, {$child->address['ward']}, {$child->address['Region']}\n";
    echo "  Birth Facility: {$child->birthFacility}\n";
    echo "\n";
}

echo "\n2. CHILDREN ELIGIBLE FOR VACCINATIONS TODAY:\n";
echo "--------------------------------------------\n";

// Vaccination schedule
$vaccinationSchedule = [
    6 => ['bOPV-1', 'Rota-1', 'DPT-HepB-Hib-1', 'PCV13-1'],
    10 => ['bOPV-2', 'Rota-2', 'DPT-HepB-Hib-2', 'PCV13-2'],
    14 => ['bOPV-3', 'Rota-3', 'DPT-HepB-Hib-3', 'PCV13-3', 'IPV'],
    39 => ['Surua Rubella-1'],
    78 => ['Surua Rubella-2']
];

foreach ($vaccinationSchedule as $weekAge => $vaccines) {
    echo "Children eligible for {$weekAge}-week vaccines:\n";
    
    $eligibleChildren = Child::whereRaw('DATEDIFF(?, date_of_birth) BETWEEN ? AND ?', [
        now()->format('Y-m-d'),
        $weekAge * 7 - 3, // 3 days tolerance
        $weekAge * 7 + 3
    ])->get();
    
    if ($eligibleChildren->count() > 0) {
        foreach ($eligibleChildren as $child) {
            $exactAge = Carbon::parse($child->date_of_birth)->diffInWeeks(now());
            echo "  - {$child->childName} (Age: {$exactAge} weeks)\n";
            echo "    Vaccines due: " . implode(', ', $vaccines) . "\n";
        }
    } else {
        echo "  No children found\n";
    }
    echo "\n";
}

echo "\n3. CHILDREN ELIGIBLE FOR VITAMINS TODAY:\n";
echo "---------------------------------------\n";

$vitaminSchedule = [6, 12, 18, 24, 30, 36, 42, 48, 54, 60]; // months

foreach ($vitaminSchedule as $monthAge) {
    echo "Children eligible for {$monthAge}-month vitamins:\n";
    
    $eligibleChildren = Child::whereRaw('DATEDIFF(?, date_of_birth) BETWEEN ? AND ?', [
        now()->format('Y-m-d'),
        $monthAge * 30 - 15, // 15 days tolerance  
        $monthAge * 30 + 15
    ])->get();
    
    if ($eligibleChildren->count() > 0) {
        foreach ($eligibleChildren as $child) {
            $exactAge = Carbon::parse($child->date_of_birth)->diffInMonths(now());
            echo "  - {$child->childName} (Age: {$exactAge} months)\n";
            echo "    Vitamin visit: " . (array_search($monthAge, $vitaminSchedule) + 1) . "\n";
        }
    } else {
        echo "  No children found\n";
    }
    echo "\n";
}

echo "\n4. TODAY'S APPOINTMENTS:\n";
echo "-----------------------\n";

$todaysAppointments = Appointment::with('child')
    ->whereDate('appointment_date', now()->format('Y-m-d'))
    ->get();

if ($todaysAppointments->count() > 0) {
    foreach ($todaysAppointments as $appointment) {
        echo "Appointment: {$appointment->appointment_name}\n";
        echo "  Child: {$appointment->child->childName}\n";
        echo "  Type: {$appointment->appointment_type}\n";
        echo "  Date: {$appointment->appointment_date}\n";
        echo "  Time: {$appointment->appointment_time}\n";
        echo "\n";
    }
} else {
    echo "No appointments scheduled for today\n";
}

echo "\n5. SUMMARY STATISTICS:\n";
echo "---------------------\n";

$totalChildren = Child::count();
$childrenUnder1Year = Child::where('date_of_birth', '>=', now()->subYear())->count();
$childrenUnder5Years = Child::where('date_of_birth', '>=', now()->subYears(5))->count();
$todaysAppointmentsCount = Appointment::whereDate('appointment_date', now()->format('Y-m-d'))->count();

echo "Total Children: {$totalChildren}\n";
echo "Children under 1 year: {$childrenUnder1Year}\n";
echo "Children under 5 years: {$childrenUnder5Years}\n";
echo "Today's appointments: {$todaysAppointmentsCount}\n";

echo "\n6. VACCINATION STATUS CHECK:\n";
echo "---------------------------\n";

// Check vaccination status for each child
foreach ($children->take(5) as $child) { // Show first 5 children
    echo "Child: {$child->childName}\n";
    $vaccinations = $child->vaccinations;
    
    foreach ($vaccinations as $vaccination) {
        echo "  - {$vaccination->vaccination_code}: {$vaccination->Hali}\n";
    }
    echo "\n";
}

echo "\n=== TEST COMPLETED ===\n";
