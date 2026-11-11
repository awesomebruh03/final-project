<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_aggregates_the_requested_range(): void
    {
        $this->travelTo('2026-11-15 12:00:00');

        $user = User::factory()->create();
        $salary = Category::factory()->for($user)->income()->create(['name' => 'Salary', 'slug' => 'salary']);
        $food = Category::factory()->for($user)->create(['name' => 'Food', 'slug' => 'food']);
        $transport = Category::factory()->for($user)->create(['name' => 'Transport', 'slug' => 'transport']);

        Transaction::factory()->for($user)->for($salary)->income()->create([
            'amount' => 1000,
            'date' => '2026-11-01',
        ]);
        Transaction::factory()->for($user)->for($food)->create([
            'amount' => 100,
            'date' => '2026-11-05',
            'time' => '09:30',
        ]);
        Transaction::factory()->for($user)->for($food)->create([
            'amount' => 50,
            'date' => '2026-11-06',
            'time' => '18:00',
        ]);
        Transaction::factory()->for($user)->for($transport)->create([
            'amount' => 25,
            'date' => '2026-11-07',
            'time' => null,
        ]);

        // Outside of the requested range and must be ignored.
        Transaction::factory()->for($user)->for($food)->create([
            'amount' => 999,
            'date' => '2026-10-31',
            'time' => '12:00',
        ]);
        Transaction::factory()->for($user)->for($salary)->income()->create([
            'amount' => 5000,
            'date' => '2026-12-01',
        ]);

        $goal = SavingsGoal::factory()->for($user)->create();
        SavingsContribution::factory()->for($goal, 'goal')->create([
            'amount' => 200,
            'date' => '2026-11-10',
        ]);
        SavingsContribution::factory()->for($goal, 'goal')->create([
            'amount' => 300,
            'date' => '2026-10-01',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/summary?from=2026-11-01&to=2026-11-30')
            ->assertOk()
            ->assertExactJson([
                'from' => '2026-11-01',
                'to' => '2026-11-30',
                'currency' => 'USD',
                'income' => 1000,
                'expenses' => 175,
                'net' => 825,
                'savings' => 200,
                'by_category' => [
                    ['category' => 'Food', 'total' => 150],
                    ['category' => 'Transport', 'total' => 25],
                ],
                'by_hour' => [
                    ['hour' => 9, 'total' => 100],
                    ['hour' => 18, 'total' => 50],
                ],
            ]);
    }

    public function test_summary_defaults_to_the_current_month(): void
    {
        $this->travelTo('2026-11-15 12:00:00');

        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Transaction::factory()->for($user)->for($category)->create([
            'amount' => 40,
            'date' => '2026-11-20',
        ]);
        Transaction::factory()->for($user)->for($category)->create([
            'amount' => 70,
            'date' => '2026-10-31',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/summary')
            ->assertOk()
            ->assertJsonPath('from', '2026-11-01')
            ->assertJsonPath('to', '2026-11-30')
            ->assertJsonPath('expenses', 40);
    }

    public function test_summary_returns_zeroes_when_there_is_no_data(): void
    {
        $this->travelTo('2026-11-15 12:00:00');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/summary')
            ->assertOk()
            ->assertExactJson([
                'from' => '2026-11-01',
                'to' => '2026-11-30',
                'currency' => 'USD',
                'income' => 0,
                'expenses' => 0,
                'net' => 0,
                'savings' => 0,
                'by_category' => [],
                'by_hour' => [],
            ]);
    }

    public function test_summary_returns_400_when_from_is_after_to(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/summary?from=2026-11-30&to=2026-11-01')
            ->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    public function test_summary_returns_401_without_a_token(): void
    {
        $this->getJson('/api/summary')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }
}
