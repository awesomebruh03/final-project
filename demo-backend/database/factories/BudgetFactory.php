<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
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
            'category_id' => null,
            'amount' => fake()->randomFloat(2, 50, 1000),
            'currency' => 'USD',
            'period' => 'monthly',
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => null,
        ];
    }
}
