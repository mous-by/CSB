<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Enums\LogicielStatus;
use App\Models\Category;
use App\Models\Logiciel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logiciel>
 */
class LogicielFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()).' '.fake()->randomNumber(3),
            'summary' => fake()->sentence(12),
            'problem' => fake()->sentence(15),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory()->of(CategoryType::Logiciel),
            'features' => ['Gestion des élèves', 'Bulletins de notes'],
            'platforms' => ['web', 'android'],
            'status' => LogicielStatus::Disponible,
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => LogicielStatus::Archive]);
    }
}
