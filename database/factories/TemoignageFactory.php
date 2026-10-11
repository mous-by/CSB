<?php

namespace Database\Factories;

use App\Models\Temoignage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Temoignage>
 */
class TemoignageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_name' => fake()->name(),
            'author_title' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'content' => fake()->sentence(20),
            'sort_order' => 0,
            'is_published' => true,
        ];
    }
}
