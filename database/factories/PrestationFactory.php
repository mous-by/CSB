<?php

namespace Database\Factories;

use App\Models\Prestation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prestation>
 */
class PrestationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(3, true)),
            'icon' => 'bx-wrench',
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 50),
            'is_published' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
