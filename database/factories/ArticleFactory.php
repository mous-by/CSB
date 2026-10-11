<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->unique()->words(5, true)),
            'excerpt' => fake()->sentence(15),
            'body' => "## Introduction\n\n".fake()->paragraphs(3, true),
            'category_id' => Category::factory()->of(CategoryType::Article),
            'is_published' => true,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false, 'published_at' => null]);
    }
}
