<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Child;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\VitaminAndDeworming;
use App\Models\GrowthRecords;
use App\Models\HealthCareProvider;
use Database\Seeders\VaccinationSeeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class ChildVaccinationInitializationTest extends TestCase
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

        // Create a healthcare provider for testing
        HealthCareProvider::factory()->create([
            'id' => 1,
            'name' => 'Test Health Center',
            'facility' => 'Test Facility'
        ]);
    }
    #[Test]
    public function child_creation_initializes_all_vaccinations()
    {
        // Get expected vaccination codes
        $expectedVaccinationCodes = VaccinationSeeder::getVaccinationCodes();

        // Create a newborn child (0 weeks old)
        $childData = [
            'childNo' => 'TEST001',
            'user_id' => User::factory()->create()->id,
            'childName' => 'Test Child',
            'date_of_birth' => Carbon::now()->toDateString(), // Born today
            'gender' => 'Male',
            'birthWeight' => 3.5,
            'birthHeight' => 50.0,
            'fatherName' => 'Test Father',
            'motherName' => 'Test Mother',
            'birthFacility' => 'Test Hospital',
            'birthAttendant' => 'Doctor',
            'email' => 'test@example.com',
            'phoneNo' => '1234567890',
            'address' => [
                'street' => 'Test Street',
                'ward' => 'Test Ward',
                'Region' => 'Test Region'
            ],
            'motherAge' => 25
        ];

        // Create the child using the model directly (simulates ChildController behavior)
        $child = Child::create($childData);

        // Assert child was created
        $this->assertDatabaseHas('children', [
            'childNo' => 'TEST001',
            'childName' => 'Test Child'
        ]);

        // Assert all vaccination records were created
        $this->assertEquals(
            count($expectedVaccinationCodes),
            Vaccination::where('child_id', $child->id)->count(),
            'All vaccination records should be created'
        );

        // Assert each expected vaccination code exists
        foreach ($expectedVaccinationCodes as $code) {
            $this->assertDatabaseHas('vaccinations', [
                'child_id' => $child->id,
                'vaccination_code' => $code,
                'Hali' => 'inasubiri' // Should be waiting for newborn
            ]);
        }

        // Assert growth record was created
        $this->assertDatabaseHas('growth_records', [
            'child_id' => $child->id,
            'weight' => 3.5,
            'height' => 50.0
        ]);

        // Assert vitamin and deworming records were created (10 visits)
        $this->assertEquals(
            10,
            VitaminAndDeworming::where('child_id', $child->id)->count(),
            '10 vitamin and deworming records should be created'
        );

        echo "✅ Child creation test passed - All vaccinations initialized\n";
        echo "Created " . count($expectedVaccinationCodes) . " vaccination records\n";
        echo "Vaccination codes: " . implode(', ', $expectedVaccinationCodes) . "\n";
    }
    #[Test]
    public function child_creation_sets_correct_vaccination_status_for_different_ages()
    {
        // Test 1: Newborn (0 weeks) - all should be 'inasubiri'
        $newborn = Child::create([
            'childNo' => 'NEWBORN001',
            'user_id' => User::factory()->create()->id,
            'childName' => 'Newborn Child',
            'date_of_birth' => Carbon::now()->toDateString(),
            'gender' => 'Female',
            'birthWeight' => 3.2,
            'birthHeight' => 48.0,
            'fatherName' => 'Father 1',
            'motherName' => 'Mother 1',
            'birthFacility' => 'Hospital 1',
            'birthAttendant' => 'Midwife',
            'address' => ['street' => 'St1', 'ward' => 'W1', 'Region' => 'R1']
        ]);

        // For newborn, BCG and bOPVO should be waiting (current week vaccines)
        $this->assertDatabaseHas('vaccinations', [
            'child_id' => $newborn->id,
            'vaccination_code' => 'BCG',
            'Hali' => 'inasubiri'
        ]);

        // Test 2: 8-week-old child - some should be 'amekosa' (missed)
        $olderChild = Child::create([
            'childNo' => 'OLDER001',
            'user_id' => User::factory()->create()->id,
            'childName' => 'Older Child',
            'date_of_birth' => Carbon::now()->subWeeks(8)->toDateString(),
            'gender' => 'Male',
            'birthWeight' => 3.8,
            'birthHeight' => 52.0,
            'fatherName' => 'Father 2',
            'motherName' => 'Mother 2',
            'birthFacility' => 'Hospital 2',
            'birthAttendant' => 'Doctor',
            'address' => ['street' => 'St2', 'ward' => 'W2', 'Region' => 'R2']
        ]);

        // For 8-week-old, birth vaccines should be missed
        $this->assertDatabaseHas('vaccinations', [
            'child_id' => $olderChild->id,
            'vaccination_code' => 'BCG',
            'Hali' => 'amekosa'
        ]);

        // 6-week vaccines should also be missed for 8-week-old
        $this->assertDatabaseHas('vaccinations', [
            'child_id' => $olderChild->id,
            'vaccination_code' => 'bOPV-1',
            'Hali' => 'amekosa'
        ]);

        echo "✅ Age-based vaccination status test passed\n";
    }
    #[Test]
    public function child_creation_via_controller_creates_vaccinations()
    {
        // Create a user to act as admin/healthcare provider
        $user = User::factory()->create();
        $user->assignRole('admin'); // Assuming you have role system

        // Create the child data as it would come from the frontend
        $childData = [
            'childNo' => 'CTRL001',
            'childName' => 'Controller Child',
            'date_of_birth' => Carbon::now()->subWeeks(2)->toDateString(),
            'gender' => 'Female',
            'birthWeight' => 3.3,
            'birthHeight' => 49.0,
            'fatherName' => 'Controller Father',
            'motherName' => 'Controller Mother',
            'birthFacility' => 'Controller Hospital',
            'birthAttendant' => 'Nurse',
            'email' => 'controller@test.com',
            'phoneNo' => '9876543210',
            'address' => [
                'street' => 'Controller Street',
                'ward' => 'Controller Ward',
                'Region' => 'Controller Region'
            ],
            'motherAge' => 30,
            'health_care_provider_id' => 1
        ];

        // Simulate the controller request
        $response = $this->actingAs($user)->postJson('/api/healthcare/children', $childData);

        // Assert successful creation
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'child',
                'credentials_info',
                'access_token'
            ]);

        // Get the created child
        $child = Child::where('childNo', 'CTRL001')->first();
        $this->assertNotNull($child, 'Child should be created');

        // Check if vaccinations were initialized
        $vaccinationCount = Vaccination::where('child_id', $child->id)->count();
        $expectedCount = count(VaccinationSeeder::getVaccinationCodes());

        $this->assertEquals(
            $expectedCount,
            $vaccinationCount,
            "Expected {$expectedCount} vaccinations, but found {$vaccinationCount}"
        );

        echo "✅ Controller creation test passed\n";
        echo "Child ID: {$child->id}\n";
        echo "Vaccination records created: {$vaccinationCount}\n";

        // Display all created vaccinations for debugging
        $vaccinations = Vaccination::where('child_id', $child->id)->get();
        echo "Created vaccinations:\n";
        foreach ($vaccinations as $vac) {
            echo "- {$vac->vaccination_code}: {$vac->Hali}\n";
        }
    }
    #[Test]
    public function debug_child_creation_process()
    {
        echo "\n=== DEBUGGING CHILD CREATION PROCESS ===\n";

        // Count initial records
        $initialChildCount = Child::count();
        $initialVaccinationCount = Vaccination::count();

        echo "Initial children count: {$initialChildCount}\n";
        echo "Initial vaccination count: {$initialVaccinationCount}\n";

        // Create child
        echo "\nCreating child...\n";
        $child = Child::create([
            'childNo' => 'DEBUG001',
            'user_id' => User::factory()->create()->id,
            'childName' => 'Debug Child',
            'date_of_birth' => Carbon::now()->toDateString(),
            'gender' => 'Male',
            'birthWeight' => 3.0,
            'birthHeight' => 47.0,
            'fatherName' => 'Debug Father',
            'motherName' => 'Debug Mother',
            'birthFacility' => 'Debug Hospital',
            'birthAttendant' => 'Debug Doctor',
            'address' => ['street' => 'Debug St', 'ward' => 'Debug W', 'Region' => 'Debug R']
        ]);

        echo "Child created with ID: {$child->id}\n";

        // Count after creation
        $finalChildCount = Child::count();
        $finalVaccinationCount = Vaccination::count();
        $childVaccinationCount = Vaccination::where('child_id', $child->id)->count();

        echo "Final children count: {$finalChildCount}\n";
        echo "Final vaccination count: {$finalVaccinationCount}\n";
        echo "Vaccinations for this child: {$childVaccinationCount}\n";

        // Check if the created event fired
        $growthRecordExists = GrowthRecords::where('child_id', $child->id)->exists();
        echo "Growth record created: " . ($growthRecordExists ? 'YES' : 'NO') . "\n";

        // Check specific vaccinations
        $expectedCodes = VaccinationSeeder::getVaccinationCodes();
        echo "Expected vaccination codes (" . count($expectedCodes) . "): " . implode(', ', $expectedCodes) . "\n";

        $actualCodes = Vaccination::where('child_id', $child->id)->pluck('vaccination_code')->toArray();
        echo "Actual vaccination codes (" . count($actualCodes) . "): " . implode(', ', $actualCodes) . "\n";

        $missingCodes = array_diff($expectedCodes, $actualCodes);
        if (!empty($missingCodes)) {
            echo "❌ Missing vaccination codes: " . implode(', ', $missingCodes) . "\n";
        } else {
            echo "✅ All vaccination codes present\n";
        }

        // This test always passes, it's just for debugging
        $this->assertTrue(true);
    }
    #[Test]
    public function verify_vaccination_seeder_codes_accessible()
    {
        echo "\n=== VERIFYING VACCINATION SEEDER ===\n";

        try {
            $codes = VaccinationSeeder::getVaccinationCodes();
            echo "✅ VaccinationSeeder::getVaccinationCodes() works\n";
            echo "Returned " . count($codes) . " codes: " . implode(', ', $codes) . "\n";

            $this->assertIsArray($codes);
            $this->assertGreaterThan(0, count($codes));
        } catch (\Exception $e) {
            echo "❌ Error accessing VaccinationSeeder: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
