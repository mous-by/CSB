<?php

namespace Database\Factories;

use App\Models\Realisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Realisation>
 */
class RealisationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Projet '.fake()->unique()->words(2, true),
            'client_name' => fake()->company(),
            'sector' => 'Éducation',
            'year' => 2026,
            'summary' => fake()->sentence(12),
            'context' => fake()->paragraph(),
            'need' => fake()->paragraph(),
            'solution' => fake()->paragraph(),
            'technologies' => ['Laravel', 'MySQL'],
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
