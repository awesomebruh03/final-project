<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_an_overall_budget_with_current_period_spending(): void
    {
        $this->travelTo('2026-11-15 12:00:00');

        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->create([
            'amount' => 500,
            'period' => 'monthly',
            'starts_on' => '2026-11-01',
        ]);

        Transaction::factory()->for($user)->for($category)->create(['amount' => 100, 'date' => '2026-11-05']);
        Transaction::factory()->for($user)->for($category)->create(['amount' => 50, 'date' => '2026-11-20']);
        Transaction::factory()->for($user)->for($category)->create(['amount' => 999, 'date' => '2026-10-31']);

        Sanctum::actingAs($user);

        $this->getJson('/api/budgets')
            ->assertOk()
            ->assertExactJson([[
                'id' => $budget->id,
                'category' => null,
                'amount' => 500,
                'currency' => 'USD',
                'period' => 'monthly',
                'starts_on' => '2026-11-01',
                'ends_on' => null,
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
                'spent' => 150,
                'remaining' => 350,
                'percentage' => 30,
            ]]);
    }

    public function test_index_filters_spending_by_the_budget_category(): void
    {
        $this->travelTo('2026-11-15 12:00:00');

        $user = User::factory()->create();
        $food = Category::factory()->for($user)->create(['name' => 'Food', 'slug' => 'food']);
        $transport = Category::factory()->for($user)->create(['name' => 'Transport', 'slug' => 'transport']);
        Budget::factory()->for($user)->for($food)->create([
            'amount' => 200,
            'starts_on' => '2026-11-01',
        ]);

        Transaction::factory()->for($user)->for($food)->create(['amount' => 80, 'date' => '2026-11-05']);
        Transaction::factory()->for($user)->for($transport)->create(['amount' => 40, 'date' => '2026-11-06']);

        Sanctum::actingAs($user);

        $this->getJson('/api/budgets')
            ->assertOk()
            ->assertJsonPath('0.category', 'Food')
            ->assertJsonPath('0.spent', 80)
            ->assertJsonPath('0.remaining', 120);
    }

    public function test_store_creates_a_category_budget(): void
    {
        $this->travelTo('2026-11-15 12:00:00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/budgets', [
            'category' => 'Food',
            'amount' => 300,
            'period' => 'monthly',
        ]);

        $response->assertCreated()
            ->assertJsonPath('category', 'Food')
            ->assertJsonPath('amount', 300)
            ->assertJsonPath('starts_on', '2026-11-15')
            ->assertJsonPath('period_end', '2026-12-14')
            ->assertJsonPath('spent', 0);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'kind' => 'expense',
            'slug' => 'food',
        ]);
    }

    public function test_store_creates_an_overall_budget_without_a_category(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/budgets', [
            'amount' => 1000,
            'period' => 'monthly',
        ])->assertCreated()
            ->assertJsonPath('category', null);
    }

    public function test_destroy_deletes_the_users_own_budget(): void
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/budgets/{$budget->id}")
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->assertModelMissing($budget);
    }

    public function test_destroy_returns_404_for_another_users_budget(): void
    {
        $budget = Budget::factory()->for(User::factory())->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/budgets/{$budget->id}")
            ->assertNotFound();

        $this->assertModelExists($budget);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidBudgetPayloads(): array
    {
        return [
            'zero amount' => [['amount' => 0, 'period' => 'monthly']],
            'missing period' => [['amount' => 100]],
            'unknown period' => [['amount' => 100, 'period' => 'daily']],
            'end before start' => [['amount' => 100, 'period' => 'monthly', 'starts_on' => '2026-11-10', 'ends_on' => '2026-11-01']],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidBudgetPayloads')]
    public function test_store_returns_400_for_invalid_input(array $payload): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/budgets', $payload)
            ->assertStatus(400)
            ->assertJsonStructure(['message']);
    }
}
