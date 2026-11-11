<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Food', 'Transport', 'Bills', 'Fun', 'Health', 'Shopping']);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'kind' => 'expense',
            'color' => fake()->safeHexColor(),
            'icon' => null,
            'is_archived' => false,
        ];
    }

    /**
     * Indicate that the category is used for income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => 'income',
        ]);
    }
}
