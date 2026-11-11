<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_the_authenticated_users_income(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->income()->create(['name' => 'Salary', 'slug' => 'salary']);
        $income = Transaction::factory()->for($user)->for($category)->income()->create([
            'amount' => 1000,
            'date' => '2026-11-01',
        ]);
        Transaction::factory()->for(User::factory())->income()->create();
        Transaction::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/incomes')
            ->assertOk()
            ->assertExactJson([[
                'id' => $income->id,
                'type' => 'income',
                'amount' => 1000,
                'date' => '2026-11-01',
                'time' => null,
                'category' => 'Salary',
                'note' => $income->note,
                'nature' => null,
            ]]);
    }

    public function test_store_creates_income_with_an_income_category(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/incomes', [
            'amount' => 1000,
            'date' => '2026-11-01',
            'category' => 'Salary',
            'nature' => 'fixed',
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'income')
            ->assertJsonPath('amount', 1000)
            ->assertJsonPath('category', 'Salary')
            ->assertJsonPath('note', '')
            ->assertJsonPath('nature', 'fixed');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'kind' => 'income',
            'slug' => 'salary',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'income',
            'amount' => 1000,
            'nature' => 'fixed',
        ]);
    }

    public function test_destroy_deletes_income_but_not_expenses(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->for($user)->income()->create();
        $expenseCategory = Category::factory()->for($user)->create();
        $income = Transaction::factory()->for($user)->for($incomeCategory)->income()->create();
        $expense = Transaction::factory()->for($user)->for($expenseCategory)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/incomes/{$income->id}")
            ->assertOk()
            ->assertExactJson(['ok' => true]);
        $this->assertModelMissing($income);

        $this->deleteJson("/api/incomes/{$expense->id}")
            ->assertNotFound();
        $this->assertModelExists($expense);
    }

    public function test_income_endpoints_return_401_without_a_token(): void
    {
        $this->getJson('/api/incomes')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }
}
