<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
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
            'category_id' => Category::factory(),
            'note' => fake()->sentence(),
            'nature' => 'fixed',
            'frequency' => 'monthly',
            'interval' => 1,
            'starts_on' => now()->toDateString(),
            'ends_on' => null,
            'next_run_on' => now()->toDateString(),
            'is_active' => true,
        ];
    }
}
