<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecurringTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_a_recurring_template(): void
    {
        $this->travelTo('2026-11-15 08:00:00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/recurring', [
            'type' => 'expense',
            'amount' => 15.99,
            'category' => 'Subscriptions',
            'note' => 'netflix',
            'nature' => 'fixed',
            'frequency' => 'monthly',
            'starts_on' => '2026-11-15',
        ]);

        $response->assertCreated()
            ->assertExactJson([
                'id' => $response->json('id'),
                'type' => 'expense',
                'amount' => 15.99,
                'currency' => 'USD',
                'category' => 'Subscriptions',
                'note' => 'netflix',
                'nature' => 'fixed',
                'frequency' => 'monthly',
                'interval' => 1,
                'starts_on' => '2026-11-15',
                'ends_on' => null,
                'next_run_on' => '2026-11-15',
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $response->json('id'),
            'user_id' => $user->id,
            'frequency' => 'monthly',
            'next_run_on' => '2026-11-15',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'kind' => 'expense',
            'slug' => 'subscriptions',
        ]);
    }

    public function test_generate_materializes_due_occurrences_and_advances_the_schedule(): void
    {
        $this->travelTo('2026-11-15 08:00:00');

        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Subscriptions', 'slug' => 'subscriptions']);
        $template = RecurringTransaction::factory()->for($user)->for($category)->create([
            'amount' => 15.99,
            'note' => 'netflix',
            'nature' => 'fixed',
            'frequency' => 'monthly',
            'interval' => 1,
            'starts_on' => '2026-11-15',
            'next_run_on' => '2026-11-15',
        ]);

        $this->artisan('recurring:generate')
            ->expectsOutput('Generated 1 recurring transaction(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'recurring_id' => $template->id,
            'type' => 'expense',
            'amount' => 15.99,
            'date' => '2026-11-15',
            'category_id' => $category->id,
            'note' => 'netflix',
            'nature' => 'fixed',
        ]);

        $template->refresh();

        $this->assertSame('2026-12-15', $template->next_run_on->toDateString());
        $this->assertTrue($template->is_active);
    }

    public function test_generate_does_not_duplicate_occurrences_when_run_again(): void
    {
        $this->travelTo('2026-11-15 08:00:00');

        $user = User::factory()->create();
        $template = RecurringTransaction::factory()->for($user)->create([
            'frequency' => 'monthly',
            'starts_on' => '2026-11-15',
            'next_run_on' => '2026-11-15',
        ]);

        $this->artisan('recurring:generate')->assertExitCode(0);
        $this->assertSame(1, Transaction::count());

        // Rewind the schedule to prove the unique constraint keeps reruns idempotent.
        $template->update(['next_run_on' => '2026-11-15', 'is_active' => true]);

        $this->artisan('recurring:generate')
            ->expectsOutput('Generated 0 recurring transaction(s).')
            ->assertExitCode(0);

        $this->assertSame(1, Transaction::count());
        $this->assertSame('2026-12-15', $template->fresh()->next_run_on->toDateString());
    }

    public function test_generate_catches_up_missed_daily_occurrences(): void
    {
        $this->travelTo('2026-11-15 08:00:00');

        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $template = RecurringTransaction::factory()->for($user)->for($category)->create([
            'frequency' => 'daily',
            'interval' => 1,
            'starts_on' => '2026-11-12',
            'next_run_on' => '2026-11-12',
        ]);

        $this->artisan('recurring:generate')->assertExitCode(0);

        foreach (['2026-11-12', '2026-11-13', '2026-11-14', '2026-11-15'] as $date) {
            $this->assertDatabaseHas('transactions', [
                'recurring_id' => $template->id,
                'date' => $date,
            ]);
        }

        $this->assertSame(4, $template->transactions()->count());

        $template->refresh();

        $this->assertSame('2026-11-16', $template->next_run_on->toDateString());
        $this->assertTrue($template->is_active);
    }

    public function test_generate_deactivates_a_template_once_it_passes_its_end_date(): void
    {
        $this->travelTo('2026-12-05 08:00:00');

        $user = User::factory()->create();
        $template = RecurringTransaction::factory()->for($user)->create([
            'frequency' => 'monthly',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-30',
            'next_run_on' => '2026-11-01',
        ]);

        $this->artisan('recurring:generate')->assertExitCode(0);

        $this->assertDatabaseHas('transactions', [
            'recurring_id' => $template->id,
            'date' => '2026-11-01',
        ]);

        $template->refresh();

        $this->assertFalse($template->is_active);
        $this->assertSame('2026-12-01', $template->next_run_on->toDateString());
    }

    public function test_destroy_deletes_only_the_owners_template_and_keeps_generated_transactions(): void
    {
        $this->travelTo('2026-11-15 08:00:00');

        $user = User::factory()->create();
        $template = RecurringTransaction::factory()->for($user)->create([
            'starts_on' => '2026-11-15',
            'next_run_on' => '2026-11-15',
        ]);

        $this->artisan('recurring:generate')->assertExitCode(0);

        $transaction = $template->transactions()->firstOrFail();
        $otherTemplate = RecurringTransaction::factory()->for(User::factory())->create();

        Sanctum::actingAs($user);

        $this->deleteJson("/api/recurring/{$otherTemplate->id}")
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->assertModelExists($otherTemplate);

        $this->deleteJson("/api/recurring/{$template->id}")
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        $this->assertModelMissing($template);
        $this->assertModelExists($transaction);
        $this->assertNull($transaction->fresh()->recurring_id);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidRecurringPayloads(): array
    {
        $valid = [
            'type' => 'expense',
            'amount' => 15,
            'category' => 'Subscriptions',
            'frequency' => 'monthly',
            'starts_on' => '2026-11-15',
        ];

        return [
            'zero amount' => [array_merge($valid, ['amount' => 0])],
            'unknown type' => [array_merge($valid, ['type' => 'transfer'])],
            'unknown frequency' => [array_merge($valid, ['frequency' => 'hourly'])],
            'interval below one' => [array_merge($valid, ['interval' => 0])],
            'ends before start' => [array_merge($valid, ['ends_on' => '2026-11-01'])],
            'missing start date' => [['type' => 'expense', 'amount' => 15, 'category' => 'Subscriptions', 'frequency' => 'monthly']],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidRecurringPayloads')]
    public function test_store_returns_400_for_invalid_input(array $payload): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/recurring', $payload)
            ->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    public function test_recurring_endpoints_return_401_without_a_token(): void
    {
        $template = RecurringTransaction::factory()->create();

        $this->getJson('/api/recurring')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->postJson('/api/recurring', [
            'type' => 'expense',
            'amount' => 15,
            'category' => 'Subscriptions',
            'frequency' => 'monthly',
            'starts_on' => '2026-11-15',
        ])->assertStatus(401);

        $this->deleteJson("/api/recurring/{$template->id}")
            ->assertStatus(401);

        $this->assertModelExists($template);
    }
}
