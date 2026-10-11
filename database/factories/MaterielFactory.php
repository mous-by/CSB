<?php

namespace Database\Factories;

use App\Enums\Availability;
use App\Enums\CategoryType;
use App\Enums\MaterielCondition;
use App\Models\Category;
use App\Models\Materiel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materiel>
 */
class MaterielFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'designation' => 'Ordinateur portable '.fake()->unique()->bothify('??-###'),
            'category_id' => Category::factory()->of(CategoryType::Materiel),
            'brand' => 'HP',
            'model' => 'ProBook '.fake()->numberBetween(400, 499),
            'summary' => fake()->sentence(10),
            'specs' => [['Processeur', 'Intel Core i5'], ['Mémoire', '8 Go']],
            'condition' => MaterielCondition::Neuf,
            'availability' => Availability::Disponible,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
