<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_the_authenticated_users_expenses(): void
    {
        $user = User::factory()->create();
        $category = $this->categoryFor($user);
        $ownExpense = Transaction::factory()->for($user)->for($category)->create([
            'amount' => 250.5,
            'date' => '2026-11-08',
            'note' => 'lunch with friends',
        ]);
        Transaction::factory()->for(User::factory())->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/expenses');

        $response->assertOk()
            ->assertExactJson([[
                'id' => $ownExpense->id,
                'type' => 'expense',
                'amount' => 250.5,
                'date' => '2026-11-08',
                'time' => null,
                'category' => 'Food',
                'note' => 'lunch with friends',
                'nature' => null,
            ]]);
    }

    public function test_store_creates_an_expense_and_returns_201(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/expenses', [
            'amount' => 250.5,
            'date' => '2026-11-08',
            'time' => '13:45',
            'category' => 'Food',
            'note' => 'lunch',
            'nature' => 'fixed',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'type', 'amount', 'date', 'time', 'category', 'note', 'nature'])
            ->assertJsonPath('type', 'expense')
            ->assertJsonPath('amount', 250.5)
            ->assertJsonPath('date', '2026-11-08')
            ->assertJsonPath('time', '13:45')
            ->assertJsonPath('category', 'Food')
            ->assertJsonPath('note', 'lunch')
            ->assertJsonPath('nature', 'fixed');

        $this->assertIsInt($response->json('id'));

        $this->assertDatabaseHas('transactions', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
            'type' => 'expense',
            'amount' => 250.5,
            'date' => '2026-11-08',
            'note' => 'lunch',
            'nature' => 'fixed',
        ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'kind' => 'expense',
            'name' => 'Food',
            'slug' => 'food',
        ]);
    }

    public function test_store_reuses_an_existing_category_case_insensitively(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/expenses', [
            'amount' => 10,
            'date' => '2026-11-08',
            'category' => 'Food',
        ])->assertCreated();

        $this->postJson('/api/expenses', [
            'amount' => 20,
            'date' => '2026-11-09',
            'category' => '  food  ',
        ])->assertCreated();

        $this->assertSame(1, $user->categories()->count());
    }

    public function test_store_returns_an_empty_note_when_none_is_given(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/expenses', [
            'amount' => 10,
            'date' => '2026-11-08',
            'category' => 'Food',
        ]);

        $response->assertCreated()
            ->assertJsonPath('note', '')
            ->assertJsonPath('time', null)
            ->assertJsonPath('nature', null);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'note' => null,
        ]);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidExpensePayloads(): array
    {
        $valid = ['amount' => 10, 'date' => '2026-11-08', 'category' => 'Food'];

        return [
            'zero amount' => [array_merge($valid, ['amount' => 0])],
            'negative amount' => [array_merge($valid, ['amount' => -5])],
            'non numeric amount' => [array_merge($valid, ['amount' => 'abc'])],
            'invalid calendar date' => [array_merge($valid, ['date' => '2026-02-30'])],
            'date with a timestamp' => [array_merge($valid, ['date' => '2026-11-08T10:00:00'])],
            'invalid time' => [array_merge($valid, ['time' => '25:99'])],
            'unknown nature' => [array_merge($valid, ['nature' => 'sometimes'])],
            'empty category' => [array_merge($valid, ['category' => ''])],
            'category longer than 50 characters' => [array_merge($valid, ['category' => str_repeat('a', 51)])],
            'missing category' => [Arr::except($valid, ['category'])],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidExpensePayloads')]
    public function test_store_returns_400_for_invalid_input(array $payload): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/expenses', $payload);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);

        $this->assertNotEmpty($response->json('message'));
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_destroy_deletes_one_of_the_users_own_expenses(): void
    {
        $user = User::factory()->create();
        $expense = Transaction::factory()->for($user)->for($this->categoryFor($user))->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/expenses/{$expense->id}")
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->assertModelMissing($expense);
    }

    public function test_destroy_returns_404_for_another_users_expense(): void
    {
        $user = User::factory()->create();
        $otherExpense = Transaction::factory()->for(User::factory())->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/expenses/{$otherExpense->id}")
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->assertModelExists($otherExpense);
    }

    public function test_destroy_returns_404_when_an_income_is_deleted_through_expenses(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->income()->create(['name' => 'Salary', 'slug' => 'salary']);
        $income = Transaction::factory()->for($user)->for($category)->income()->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/expenses/{$income->id}")
            ->assertNotFound();

        $this->assertModelExists($income);
    }

    public function test_destroy_returns_404_for_a_missing_expense(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/expenses/999999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_expense_endpoints_return_401_without_a_token(): void
    {
        $expense = Transaction::factory()->create();

        $this->getJson('/api/expenses')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->postJson('/api/expenses', [
            'amount' => 10,
            'date' => '2026-11-08',
            'category' => 'Food',
        ])->assertStatus(401);

        $this->deleteJson("/api/expenses/{$expense->id}")
            ->assertStatus(401);

        $this->assertModelExists($expense);
    }

    private function categoryFor(User $user, string $name = 'Food'): Category
    {
        return Category::factory()->for($user)->create([
            'name' => $name,
            'slug' => strtolower($name),
        ]);
    }
}
