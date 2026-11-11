<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 1, 500),
            'currency' => 'USD',
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'time' => null,
            'category_id' => Category::factory(),
            'note' => fake()->sentence(),
            'nature' => null,
            'recurring_id' => null,
        ];
    }

    /**
     * Indicate that the transaction is income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'income',
        ]);
    }
}
