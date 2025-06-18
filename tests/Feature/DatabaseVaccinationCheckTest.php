<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\Child;
use App\Models\Vaccination;
use Database\Seeders\VaccinationSeeder;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class DatabaseVaccinationCheckTest extends TestCase
{
    use DatabaseMigrations;

    #[Test]
    public function check_actual_database_vaccination_initialization()
    {
        echo "\n=== CHECKING ACTUAL DATABASE VACCINATION INITIALIZATION ===\n";

        // Run migrations and seeders to set up the database properly
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\HealthCareProviderSeeder']);

        echo "✅ Database migrated and seeded\n";

        // Count existing records
        $existingChildren = Child::count();
        $existingVaccinations = Vaccination::count();

        echo "Existing children in database: {$existingChildren}\n";
        echo "Existing vaccinations in database: {$existingVaccinations}\n";

        // Get expected vaccination codes
        $expectedCodes = VaccinationSeeder::getVaccinationCodes();
        echo "Expected vaccination codes (" . count($expectedCodes) . "): " . implode(', ', $expectedCodes) . "\n";

        // Create a new child using Child::create (same as ChildController does)
        $child = Child::create([
            'childNo' => 'DB_TEST_001',
            'user_id' => \App\Models\User::factory()->create()->id,
            'childName' => 'Database Test Child',
            'date_of_birth' => Carbon::now()->subWeeks(4)->toDateString(),
            'gender' => 'Male',
            'birthWeight' => 3.2,
            'birthHeight' => 49.0,
            'fatherName' => 'DB Test Father',
            'motherName' => 'DB Test Mother',
            'birthFacility' => 'DB Test Hospital',
            'birthAttendant' => 'DB Test Doctor',
            'address' => [
                'street' => 'DB Test Street',
                'ward' => 'DB Test Ward',
                'Region' => 'DB Test Region'
            ]
        ]);

        echo "✅ Child created with ID: {$child->id}\n";

        // Check vaccination creation immediately
        $childVaccinations = Vaccination::where('child_id', $child->id)->get();
        echo "Vaccinations found for child {$child->id}: " . $childVaccinations->count() . "\n";

        if ($childVaccinations->count() > 0) {
            echo "Vaccination details:\n";
            foreach ($childVaccinations as $vac) {
                echo "- ID: {$vac->id}, Code: {$vac->vaccination_code}, Status: {$vac->Hali}, Child ID: {$vac->child_id}\n";
            }
        } else {
            echo "❌ NO VACCINATIONS FOUND! This indicates a problem.\n";

            // Debug: Check if Child model events are being fired
            echo "\nDEBUG: Checking if model events are working...\n";

            // Check if growth record was created (this also happens in the created event)
            $growthRecord = \App\Models\GrowthRecords::where('child_id', $child->id)->first();
            if ($growthRecord) {
                echo "✅ Growth record found - Child model 'created' event DID fire\n";
                echo "   This means the vaccination creation code should have run too\n";
            } else {
                echo "❌ No growth record found - Child model 'created' event did NOT fire\n";
                echo "   This explains why vaccinations weren't created\n";
            }

            // Check if VaccinationSeeder::getVaccinationCodes() works
            try {
                $codes = VaccinationSeeder::getVaccinationCodes();
                echo "✅ VaccinationSeeder::getVaccinationCodes() returns " . count($codes) . " codes\n";
            } catch (\Exception $e) {
                echo "❌ VaccinationSeeder::getVaccinationCodes() failed: " . $e->getMessage() . "\n";
            }
        }

        // Always pass the test - this is just for information gathering
        $this->assertTrue(true, 'Information gathering test');
    }

    #[Test]
    public function check_if_child_model_has_correct_boot_method()
    {
        echo "\n=== CHECKING CHILD MODEL BOOT METHOD ===\n";

        // Use reflection to check if the Child model has the booted method
        $reflection = new \ReflectionClass(\App\Models\Child::class);

        if ($reflection->hasMethod('booted')) {
            echo "✅ Child model has 'booted' method\n";

            $bootedMethod = $reflection->getMethod('booted');
            $bootedMethod->setAccessible(true);

            // Get the method source (this might not work in all environments)
            $fileName = $reflection->getFileName();
            $startLine = $bootedMethod->getStartLine();
            $endLine = $bootedMethod->getEndLine();

            echo "Method location: {$fileName}:{$startLine}-{$endLine}\n";
        } else {
            echo "❌ Child model does NOT have 'booted' method\n";
        }

        // Check if Child model extends Model properly
        $parentClass = $reflection->getParentClass();
        echo "Child model extends: " . ($parentClass ? $parentClass->getName() : 'Nothing') . "\n";

        // Check if HasFactory trait is used
        $traits = $reflection->getTraitNames();
        echo "Child model uses traits: " . implode(', ', $traits) . "\n";

        $this->assertTrue(true, 'Information gathering test');
    }
}
