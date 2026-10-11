<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => CategoryType::Logiciel,
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function of(CategoryType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
