<?php

namespace Database\Factories;

use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsContribution>
 */
class SavingsContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goal_id' => SavingsGoal::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'date' => now()->toDateString(),
            'note' => null,
            'transaction_id' => null,
        ];
    }
}
