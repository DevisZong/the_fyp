<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Child;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\HealthCareProvider;
use Database\Seeders\VaccinationSeeder;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class SimpleChildVaccinationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'child']);
        \Spatie\Permission\Models\Role::create(['name' => 'nurse']);
        \Spatie\Permission\Models\Role::create(['name' => 'doctor']);

        // Create a healthcare provider
        HealthCareProvider::factory()->create(['id' => 1]);
    }

    #[Test]
    public function direct_child_creation_creates_all_vaccinations()
    {
        echo "\n=== TESTING DIRECT CHILD CREATION ===\n";

        // Create a child directly (simulating what ChildController does)
        $child = Child::create([
            'childNo' => 'DIRECT001',
            'user_id' => User::factory()->create()->id,
            'childName' => 'Direct Child',
            'date_of_birth' => Carbon::now()->subWeeks(2)->toDateString(),
            'gender' => 'Male',
            'birthWeight' => 3.5,
            'birthHeight' => 50.0,
            'fatherName' => 'Direct Father',
            'motherName' => 'Direct Mother',
            'birthFacility' => 'Direct Hospital',
            'birthAttendant' => 'Doctor',
            'address' => ['street' => 'Direct St', 'ward' => 'Direct Ward', 'Region' => 'Direct Region']
        ]);

        echo "✅ Child created with ID: {$child->id}\n";

        // Check vaccination creation
        $vaccinationCount = Vaccination::where('child_id', $child->id)->count();
        $expectedCount = count(VaccinationSeeder::getVaccinationCodes());

        echo "Expected vaccination count: {$expectedCount}\n";
        echo "Actual vaccination count: {$vaccinationCount}\n";

        $this->assertEquals($expectedCount, $vaccinationCount);

        // Display all vaccinations
        $vaccinations = Vaccination::where('child_id', $child->id)->get();
        echo "Vaccination records:\n";
        foreach ($vaccinations as $vac) {
            echo "- {$vac->vaccination_code}: {$vac->Hali} (No: {$vac->vaccination_no})\n";
        }

        // Check that child exists in database
        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'childNo' => 'DIRECT001'
        ]);

        echo "✅ All assertions passed!\n";
    }

    #[Test]
    public function verify_child_model_events_are_working()
    {
        echo "\n=== TESTING CHILD MODEL EVENTS ===\n";

        $initialCount = Vaccination::count();
        echo "Initial vaccination count in database: {$initialCount}\n";

        // Create multiple children to verify events work consistently
        for ($i = 1; $i <= 3; $i++) {
            $child = Child::create([
                'childNo' => "EVENT{$i}",
                'user_id' => User::factory()->create()->id,
                'childName' => "Event Child {$i}",
                'date_of_birth' => Carbon::now()->subWeeks($i)->toDateString(),
                'gender' => $i % 2 == 0 ? 'Female' : 'Male',
                'birthWeight' => 3.0 + ($i * 0.2),
                'birthHeight' => 48.0 + ($i * 1.0),
                'fatherName' => "Father {$i}",
                'motherName' => "Mother {$i}",
                'birthFacility' => "Hospital {$i}",
                'birthAttendant' => "Attendant {$i}",
                'address' => ['street' => "St{$i}", 'ward' => "Ward{$i}", 'Region' => "Region{$i}"]
            ]);

            $childVaccinations = Vaccination::where('child_id', $child->id)->count();
            echo "Child {$i} (ID: {$child->id}) has {$childVaccinations} vaccinations\n";
        }

        $finalCount = Vaccination::count();
        $expectedIncrease = 3 * count(VaccinationSeeder::getVaccinationCodes());
        $actualIncrease = $finalCount - $initialCount;

        echo "Expected increase: {$expectedIncrease}\n";
        echo "Actual increase: {$actualIncrease}\n";
        echo "Final vaccination count: {$finalCount}\n";

        $this->assertEquals($expectedIncrease, $actualIncrease);
        echo "✅ Child model events are working correctly!\n";
    }
}
