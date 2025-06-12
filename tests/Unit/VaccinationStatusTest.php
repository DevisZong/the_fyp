<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Child;
use App\Models\Vaccination;
use App\Models\HealthCareProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VaccinationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_vaccinations_have_mixed_statuses()
    {
        // Create a healthcare provider first (needed for completed vaccinations)
        HealthCareProvider::factory()->create();

        // Create a child who is 20 weeks old
        $child = Child::factory()->create([
            'date_of_birth' => now()->subWeeks(20)
        ]);

        // Get all vaccinations for this child
        $vaccinations = Vaccination::where('child_id', $child->id)->get();

        // Check we have all three statuses
        $this->assertTrue($vaccinations->contains('Hali', 'imekamilika'), 'Should have some completed vaccinations');
        $this->assertTrue($vaccinations->contains('Hali', 'amekosa'), 'Should have some missed vaccinations');
        $this->assertTrue($vaccinations->contains('Hali', 'inasubiri'), 'Should have some pending vaccinations');

        // Check that completed vaccinations have provider and vaccination number
        $completedVaccinations = $vaccinations->where('Hali', 'imekamilika');
        foreach ($completedVaccinations as $vaccination) {
            $this->assertNotNull($vaccination->vaccination_no, 'Completed vaccination should have a number');
            $this->assertNotNull($vaccination->health_care_provider_id, 'Completed vaccination should have a provider');
        }
    }
}
