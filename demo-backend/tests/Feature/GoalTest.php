<?php

namespace Tests\Feature;

use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_progress_reflects_contributions(): void
    {
        $this->travelTo('2026-11-15 12:00:00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/goals', [
            'name' => 'New laptop',
            'target_amount' => 1000,
            'target_date' => '2027-01-01',
        ])->assertCreated()
            ->assertJsonPath('target_amount', 1000)
            ->assertJsonPath('contributed', 0)
            ->assertJsonPath('remaining', 1000)
            ->assertJsonPath('percentage', 0);

        $goal = $user->savingsGoals()->firstOrFail();

        $this->postJson("/api/goals/{$goal->id}/contributions", [
            'amount' => 250,
            'date' => '2026-11-10',
            'note' => 'bonus',
        ])->assertCreated()
            ->assertJsonPath('amount', 250)
            ->assertJsonPath('date', '2026-11-10')
            ->assertJsonPath('note', 'bonus');

        $this->getJson('/api/goals')
            ->assertOk()
            ->assertExactJson([[
                'id' => $goal->id,
                'name' => 'New laptop',
                'target_amount' => 1000,
                'currency' => 'USD',
                'target_date' => '2027-01-01',
                'status' => 'active',
                'contributed' => 250,
                'remaining' => 750,
                'percentage' => 25,
            ]]);

        $this->getJson("/api/goals/{$goal->id}/contributions")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.amount', 250);
    }

    public function test_contributions_are_scoped_to_the_goal_owner(): void
    {
        $goal = SavingsGoal::factory()->for(User::factory())->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/goals/{$goal->id}/contributions", [
            'amount' => 50,
            'date' => '2026-11-10',
        ])->assertNotFound();

        $this->getJson("/api/goals/{$goal->id}/contributions")
            ->assertNotFound();

        $this->deleteJson("/api/goals/{$goal->id}")
            ->assertNotFound();

        $this->assertModelExists($goal);
    }

    public function test_destroy_deletes_the_goal_and_its_contributions(): void
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->for($user)->create();
        $contribution = SavingsContribution::factory()->for($goal, 'goal')->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/goals/{$goal->id}")
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->assertModelMissing($goal);
        $this->assertModelMissing($contribution);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidGoalPayloads(): array
    {
        return [
            'missing name' => [['target_amount' => 100]],
            'zero target' => [['name' => 'Laptop', 'target_amount' => 0]],
            'invalid target date' => [['name' => 'Laptop', 'target_amount' => 100, 'target_date' => '2027-02-30']],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidGoalPayloads')]
    public function test_store_returns_400_for_invalid_input(array $payload): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/goals', $payload)
            ->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    public function test_goal_endpoints_return_401_without_a_token(): void
    {
        $this->getJson('/api/goals')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }
}
