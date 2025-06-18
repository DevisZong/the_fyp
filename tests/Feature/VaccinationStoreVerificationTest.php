<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Child;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\VaccinationVerification;
use App\Models\HealthCareProvider;
use Database\Seeders\VaccinationSeeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class VaccinationStoreVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $child;
    protected $healthCareProvider;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'child']);
        \Spatie\Permission\Models\Role::create(['name' => 'nurse']);
        \Spatie\Permission\Models\Role::create(['name' => 'doctor']);

        // Create healthcare provider
        $this->healthCareProvider = HealthCareProvider::factory()->create(['id' => 1]);
        // Create user for authentication
        $this->user = User::factory()->create();
        $this->user->assignRole('nurse'); // Use 'nurse' role instead of 'admin'

        // Create a child with all vaccinations initialized
        $this->child = Child::create([
            'childNo' => 'TEST_STORE_001',
            'user_id' => User::factory()->create()->id,
            'childName' => 'Store Test Child',
            'date_of_birth' => Carbon::now()->subWeeks(8)->toDateString(), // 8 weeks old
            'gender' => 'Male',
            'birthWeight' => 3.5,
            'birthHeight' => 50.0,
            'fatherName' => 'Store Father',
            'motherName' => 'Store Mother',
            'birthFacility' => 'Store Hospital',
            'birthAttendant' => 'Doctor',
            'address' => ['street' => 'Store St', 'ward' => 'Store Ward', 'Region' => 'Store Region']
        ]);
    }

    #[Test]
    public function test_store_with_verification_preserves_existing_vaccinations()
    {
        echo "\n=== TESTING STORE WITH VERIFICATION - VACCINATION PRESERVATION ===\n";

        // Get initial vaccination count for this child
        $initialVaccinationCount = Vaccination::where('child_id', $this->child->id)->count();
        $allInitialVaccinations = Vaccination::where('child_id', $this->child->id)->get();

        echo "Child ID: {$this->child->id}\n";
        echo "Initial vaccination count for child: {$initialVaccinationCount}\n";
        echo "Initial vaccinations:\n";
        foreach ($allInitialVaccinations as $vac) {
            echo "- ID: {$vac->id}, Code: {$vac->vaccination_code}, Status: {$vac->Hali}, VacNo: {$vac->vaccination_no}\n";
        }

        // Total vaccinations in entire database before
        $totalVaccinationsBefore = Vaccination::count();
        echo "\nTotal vaccinations in database before: {$totalVaccinationsBefore}\n";

        // Prepare data for store-with-verification (6-week vaccines for 8-week-old child)
        $requestData = [
            'child_id' => $this->child->id,
            'health_care_provider_id' => $this->healthCareProvider->id,
            'vaccination_codes' => ['bOPV-1', 'Rota-1'],
            'vaccination_nos' => ['VAC001', 'VAC002']
        ];

        echo "\nCalling storeWithVerification API...\n";
        echo "Request data: " . json_encode($requestData) . "\n";

        // Call storeWithVerification
        $response = $this->actingAs($this->user)
            ->postJson('/api/healthcare/vaccinations/store-with-verification', $requestData);

        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response body: " . $response->getContent() . "\n";

        // Check vaccination count after storeWithVerification
        $vaccinationCountAfterStore = Vaccination::where('child_id', $this->child->id)->count();
        $totalVaccinationsAfterStore = Vaccination::count();

        echo "\nVaccination count for child after storeWithVerification: {$vaccinationCountAfterStore}\n";
        echo "Total vaccinations in database after storeWithVerification: {$totalVaccinationsAfterStore}\n";

        // Assert that vaccination count hasn't changed
        $this->assertEquals(
            $initialVaccinationCount,
            $vaccinationCountAfterStore,
            "storeWithVerification should NOT change vaccination count"
        );

        $this->assertEquals(
            $totalVaccinationsBefore,
            $totalVaccinationsAfterStore,
            "storeWithVerification should NOT change total vaccination count in database"
        );

        // Check that verification records were created
        $verificationCount = VaccinationVerification::where('child_id', $this->child->id)->count();
        echo "Verification records created: {$verificationCount}\n";

        // Show all vaccinations after storeWithVerification
        $vaccinationsAfterStore = Vaccination::where('child_id', $this->child->id)->get();
        echo "\nAll vaccinations after storeWithVerification:\n";
        foreach ($vaccinationsAfterStore as $vac) {
            echo "- ID: {$vac->id}, Code: {$vac->vaccination_code}, Status: {$vac->Hali}, VacNo: {$vac->vaccination_no}\n";
        }

        echo "✅ storeWithVerification preserves existing vaccinations\n";
    }

    #[Test]
    public function test_verify_and_store_preserves_other_vaccinations()
    {
        echo "\n=== TESTING VERIFY AND STORE - VACCINATION PRESERVATION ===\n";

        // First, create verification records manually (simulating storeWithVerification)
        $verificationCode = '123456';
        $vaccinationCodes = ['bOPV-1', 'Rota-1'];
        $vaccinationNos = ['VAC001', 'VAC002'];

        foreach ($vaccinationCodes as $index => $code) {
            VaccinationVerification::create([
                'child_id' => $this->child->id,
                'health_care_provider_id' => $this->healthCareProvider->id,
                'vaccination_code' => $code,
                'vaccination_nos' => json_encode([$vaccinationNos[$index]]),
                'verification_code' => $verificationCode,
                'expires_at' => now()->addMinutes(10)
            ]);
        }

        // Get initial state
        $initialVaccinationCount = Vaccination::where('child_id', $this->child->id)->count();
        $totalVaccinationsBefore = Vaccination::count();
        $allInitialVaccinations = Vaccination::where('child_id', $this->child->id)->get();

        echo "Child ID: {$this->child->id}\n";
        echo "Initial vaccination count for child: {$initialVaccinationCount}\n";
        echo "Total vaccinations in database before: {$totalVaccinationsBefore}\n";

        echo "\nInitial vaccinations:\n";
        foreach ($allInitialVaccinations as $vac) {
            echo "- ID: {$vac->id}, Code: {$vac->vaccination_code}, Status: {$vac->Hali}, VacNo: {$vac->vaccination_no}\n";
        }

        // Get specific vaccinations that should be updated
        $bopv1Before = Vaccination::where('child_id', $this->child->id)
            ->where('vaccination_code', 'bOPV-1')
            ->first();
        $rota1Before = Vaccination::where('child_id', $this->child->id)
            ->where('vaccination_code', 'Rota-1')
            ->first();

        echo "\nTarget vaccinations before update:\n";
        echo "- bOPV-1: ID {$bopv1Before->id}, Status: {$bopv1Before->Hali}, VacNo: {$bopv1Before->vaccination_no}\n";
        echo "- Rota-1: ID {$rota1Before->id}, Status: {$rota1Before->Hali}, VacNo: {$rota1Before->vaccination_no}\n";

        // Prepare verifyAndStore request
        $verifyRequestData = [
            'child_id' => $this->child->id,
            'vaccination_codes' => $vaccinationCodes,
            'vaccination_nos' => $vaccinationNos,
            'verification_code' => $verificationCode
        ];

        echo "\nCalling verifyAndStoreVaccination API...\n";
        echo "Request data: " . json_encode($verifyRequestData) . "\n";

        // Call verifyAndStoreVaccination
        $response = $this->actingAs($this->user)
            ->postJson('/api/healthcare/vaccinations/verify-and-store', $verifyRequestData);

        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response body: " . $response->getContent() . "\n";

        // Check vaccination count after verifyAndStore
        $vaccinationCountAfter = Vaccination::where('child_id', $this->child->id)->count();
        $totalVaccinationsAfter = Vaccination::count();

        echo "\nVaccination count for child after verifyAndStore: {$vaccinationCountAfter}\n";
        echo "Total vaccinations in database after verifyAndStore: {$totalVaccinationsAfter}\n";

        // Assert that vaccination count hasn't changed
        $this->assertEquals(
            $initialVaccinationCount,
            $vaccinationCountAfter,
            "verifyAndStoreVaccination should NOT change vaccination count"
        );

        $this->assertEquals(
            $totalVaccinationsBefore,
            $totalVaccinationsAfter,
            "verifyAndStoreVaccination should NOT change total vaccination count in database"
        );

        // Show all vaccinations after verifyAndStore
        $vaccinationsAfter = Vaccination::where('child_id', $this->child->id)->get();
        echo "\nAll vaccinations after verifyAndStore:\n";
        foreach ($vaccinationsAfter as $vac) {
            echo "- ID: {$vac->id}, Code: {$vac->vaccination_code}, Status: {$vac->Hali}, VacNo: {$vac->vaccination_no}\n";
        }

        // Check that target vaccinations were updated correctly
        $bopv1After = Vaccination::where('child_id', $this->child->id)
            ->where('vaccination_code', 'bOPV-1')
            ->first();
        $rota1After = Vaccination::where('child_id', $this->child->id)
            ->where('vaccination_code', 'Rota-1')
            ->first();

        echo "\nTarget vaccinations after update:\n";
        echo "- bOPV-1: ID {$bopv1After->id}, Status: {$bopv1After->Hali}, VacNo: {$bopv1After->vaccination_no}\n";
        echo "- Rota-1: ID {$rota1After->id}, Status: {$rota1After->Hali}, VacNo: {$rota1After->vaccination_no}\n";

        // Assert target vaccinations were updated
        $this->assertEquals($bopv1Before->id, $bopv1After->id, "Same bOPV-1 record should be updated, not replaced");
        $this->assertEquals($rota1Before->id, $rota1After->id, "Same Rota-1 record should be updated, not replaced");
        $this->assertEquals('imekamilika', $bopv1After->Hali, "bOPV-1 should be marked as completed");
        $this->assertEquals('imekamilika', $rota1After->Hali, "Rota-1 should be marked as completed");
        $this->assertEquals('VAC001', $bopv1After->vaccination_no, "bOPV-1 should have correct vaccination number");
        $this->assertEquals('VAC002', $rota1After->vaccination_no, "Rota-1 should have correct vaccination number");

        // Check that other vaccinations remain unchanged
        $otherVaccinations = Vaccination::where('child_id', $this->child->id)
            ->whereNotIn('vaccination_code', ['bOPV-1', 'Rota-1'])
            ->get();

        echo "\nOther vaccinations should remain unchanged:\n";
        foreach ($otherVaccinations as $vac) {
            echo "- {$vac->vaccination_code}: Status {$vac->Hali}, VacNo: " . ($vac->vaccination_no ?: 'NULL') . "\n";

            // These should still be null (not completed)
            $this->assertNull($vac->vaccination_no, "{$vac->vaccination_code} should still have null vaccination_no");
        }

        echo "✅ verifyAndStoreVaccination correctly updates only target vaccinations\n";
    }

    #[Test]
    public function test_vaccination_operations_with_multiple_children()
    {
        echo "\n=== TESTING VACCINATION OPERATIONS WITH MULTIPLE CHILDREN ===\n";

        // Create second child
        $child2 = Child::create([
            'childNo' => 'TEST_STORE_002',
            'user_id' => User::factory()->create()->id,
            'childName' => 'Store Test Child 2',
            'date_of_birth' => Carbon::now()->subWeeks(6)->toDateString(),
            'gender' => 'Female',
            'birthWeight' => 3.2,
            'birthHeight' => 49.0,
            'fatherName' => 'Store Father 2',
            'motherName' => 'Store Mother 2',
            'birthFacility' => 'Store Hospital 2',
            'birthAttendant' => 'Doctor 2',
            'address' => ['street' => 'Store St 2', 'ward' => 'Store Ward 2', 'Region' => 'Store Region 2']
        ]);

        echo "Child 1 ID: {$this->child->id}\n";
        echo "Child 2 ID: {$child2->id}\n";

        $child1Vaccinations = Vaccination::where('child_id', $this->child->id)->count();
        $child2Vaccinations = Vaccination::where('child_id', $child2->id)->count();
        $totalVaccinations = Vaccination::count();

        echo "Child 1 vaccinations: {$child1Vaccinations}\n";
        echo "Child 2 vaccinations: {$child2Vaccinations}\n";
        echo "Total vaccinations: {$totalVaccinations}\n";

        // Process vaccination for child 1 only
        $verificationCode = '111111';
        VaccinationVerification::create([
            'child_id' => $this->child->id,
            'health_care_provider_id' => $this->healthCareProvider->id,
            'vaccination_code' => 'BCG',
            'vaccination_nos' => json_encode(['VAC_CHILD1_001']),
            'verification_code' => $verificationCode,
            'expires_at' => now()->addMinutes(10)
        ]);

        $verifyRequestData = [
            'child_id' => $this->child->id,
            'vaccination_codes' => ['BCG'],
            'vaccination_nos' => ['VAC_CHILD1_001'],
            'verification_code' => $verificationCode
        ];

        echo "\nProcessing vaccination for Child 1 only...\n";
        $response = $this->actingAs($this->user)
            ->postJson('/api/healthcare/vaccinations/verify-and-store', $verifyRequestData);

        echo "Response status: " . $response->getStatusCode() . "\n";

        // Check counts after processing
        $child1VaccinationsAfter = Vaccination::where('child_id', $this->child->id)->count();
        $child2VaccinationsAfter = Vaccination::where('child_id', $child2->id)->count();
        $totalVaccinationsAfter = Vaccination::count();

        echo "\nAfter processing Child 1 vaccination:\n";
        echo "Child 1 vaccinations: {$child1VaccinationsAfter}\n";
        echo "Child 2 vaccinations: {$child2VaccinationsAfter}\n";
        echo "Total vaccinations: {$totalVaccinationsAfter}\n";

        // Assert Child 2's vaccinations are untouched
        $this->assertEquals($child2Vaccinations, $child2VaccinationsAfter, "Child 2 vaccinations should be unchanged");
        $this->assertEquals($totalVaccinations, $totalVaccinationsAfter, "Total vaccinations should be unchanged");

        // Check specific vaccination was updated for Child 1
        $child1BCG = Vaccination::where('child_id', $this->child->id)
            ->where('vaccination_code', 'BCG')
            ->first();
        $child2BCG = Vaccination::where('child_id', $child2->id)
            ->where('vaccination_code', 'BCG')
            ->first();

        echo "\nChild 1 BCG: Status {$child1BCG->Hali}, VacNo: {$child1BCG->vaccination_no}\n";
        echo "Child 2 BCG: Status {$child2BCG->Hali}, VacNo: " . ($child2BCG->vaccination_no ?: 'NULL') . "\n";

        $this->assertEquals('imekamilika', $child1BCG->Hali, "Child 1 BCG should be completed");
        $this->assertEquals('VAC_CHILD1_001', $child1BCG->vaccination_no, "Child 1 BCG should have vaccination number");
        $this->assertNull($child2BCG->vaccination_no, "Child 2 BCG should still be null");

        echo "✅ Vaccination operations correctly isolate children\n";
    }
}
