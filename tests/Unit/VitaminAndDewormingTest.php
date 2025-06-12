<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Child;
use App\Models\User;
use App\Models\VitaminAndDeworming;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class VitaminAndDewormingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-06-12'); // Fix the current date for testing

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'nurse']);
        Role::create(['name' => 'doctor']);
        Role::create(['name' => 'child']);

        // Create a user with the nurse role
        $this->user = User::factory()->create();
        $this->user->assignRole('nurse');
    }

    public function test_child_over_five_years_cannot_get_vitamins()
    {
        $this->actingAs($this->user);

        // Create a child over 5 years old
        $child = Child::factory()->create([
            'date_of_birth' => '2019-01-01'
        ]);

        $response = $this->postJson("/api/healthcare/vitamin-deworming/{$child->id}", [
            'Vitamin_A' => true,
            'Deworming' => true
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Child is over 5 years old']);
    }

    public function test_cannot_exceed_ten_visits()
    {
        $this->actingAs($this->user);

        // Create a child
        $child = Child::factory()->create([
            'date_of_birth' => '2024-01-01'
        ]);

        // Create 10 visits
        for ($i = 0; $i < 10; $i++) {
            VitaminAndDeworming::factory()
                ->withStatus('imekamilika')
                ->create([
                    'child_id' => $child->id,
                    'Vitamin_A' => true,
                    'Deworming' => true
                ]);
        }

        // Try to create 11th visit
        $response = $this->postJson("/api/healthcare/vitamin-deworming/{$child->id}", [
            'Vitamin_A' => true,
            'Deworming' => true
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'All scheduled visits have been completed']);
    }

    public function test_missed_visits_are_marked_as_amekosa()
    {
        $this->actingAs($this->user);

        // Create a 2-year-old child
        $child = Child::factory()->create([
            'date_of_birth' => '2023-06-12'
        ]);

        // This should be the 4th visit (at 2 years), but no previous visits exist
        $response = $this->postJson("/api/healthcare/vitamin-deworming/{$child->id}", [
            'Vitamin_A' => true,
            'Deworming' => true
        ]);

        $response->assertStatus(201);
        $record = VitaminAndDeworming::where('child_id', $child->id)->first();
        $this->assertEquals('amekosa', $record->status);
        $this->assertEquals(3, $response->json('missed_visits'));
    }

    public function test_scheduled_visits_calculation()
    {
        $this->actingAs($this->user);

        $child = Child::factory()->create([
            'date_of_birth' => '2024-06-12'
        ]);

        $visits = VitaminAndDeworming::getScheduledVisits($child);

        $this->assertCount(10, $visits);

        // First visit should be at 6 months
        $this->assertEquals(
            '2024-12-12',
            $visits[0]->format('Y-m-d')
        );

        // Last visit should be at 5 years
        $this->assertEquals(
            '2029-06-12',
            $visits[9]->format('Y-m-d')
        );
    }

    public function test_on_time_visit_is_marked_inasubiri()
    {
        $this->actingAs($this->user);

        // Create a 6-month-old child
        $child = Child::factory()->create([
            'date_of_birth' => '2024-12-12'
        ]);

        $response = $this->postJson("/api/healthcare/vitamin-deworming/{$child->id}", [
            'Vitamin_A' => true,
            'Deworming' => true
        ]);

        $response->assertStatus(201);
        $record = VitaminAndDeworming::where('child_id', $child->id)->first();
        $this->assertEquals('inasubiri', $record->status);
        $this->assertEquals(0, $response->json('missed_visits'));
    }
}
