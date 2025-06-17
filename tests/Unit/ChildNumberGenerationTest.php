<?php

namespace Tests\Unit;

use App\Models\Child;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildNumberGenerationTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function it_generates_sequential_child_numbers_for_same_year()
    {
        // Create children born in 2025 - childNo should be automatically generated
        $user1 = User::factory()->create();
        $child1 = Child::factory()->create([
            'user_id' => $user1->id,
            'date_of_birth' => '2025-01-15'
        ]);

        $user2 = User::factory()->create();
        $child2 = Child::factory()->create([
            'user_id' => $user2->id,
            'date_of_birth' => '2025-06-20'
        ]);

        $user3 = User::factory()->create();
        $child3 = Child::factory()->create([
            'user_id' => $user3->id,
            'date_of_birth' => '2025-12-10'
        ]);

        // Assert that children get sequential numbers for the same year
        $this->assertEquals('1/2025', $child1->childNo);
        $this->assertEquals('2/2025', $child2->childNo);
        $this->assertEquals('3/2025', $child3->childNo);
    }
    /** @test */
    public function it_generates_separate_sequences_for_different_years()
    {
        // Create child born in 2024
        $user1 = User::factory()->create();
        $child2024 = Child::factory()->create([
            'user_id' => $user1->id,
            'date_of_birth' => '2024-06-15'
        ]);

        // Create child born in 2025
        $user2 = User::factory()->create();
        $child2025 = Child::factory()->create([
            'user_id' => $user2->id,
            'date_of_birth' => '2025-06-15'
        ]);

        // Create another child born in 2024
        $user3 = User::factory()->create();
        $child2024_second = Child::factory()->create([
            'user_id' => $user3->id,
            'date_of_birth' => '2024-12-20'
        ]);

        // Assert separate sequences for different years
        $this->assertEquals('1/2024', $child2024->childNo);
        $this->assertEquals('1/2025', $child2025->childNo);
        $this->assertEquals('2/2024', $child2024_second->childNo);
    }
    /** @test */
    public function it_automatically_generates_child_number_when_creating_child()
    {
        $user = User::factory()->create();

        // Create child without specifying childNo - should be auto-generated
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'date_of_birth' => '2025-06-15'
        ]);

        // Assert that childNo was automatically generated
        $this->assertEquals('1/2025', $child->childNo);
    }
}
