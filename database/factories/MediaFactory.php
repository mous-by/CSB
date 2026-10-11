<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Ligne de bibliothèque sans fichier réel (les tests du traitement d'image passent par MediaService).
 *
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::uuid()->toString();

        return [
            'path' => "medias/2026/10/{$name}.jpg",
            'thumb_path' => "medias/2026/10/{$name}-thumb.jpg",
            'original_name' => fake()->slug(2).'.jpg',
            'mime' => 'image/jpeg',
            'size' => 120000,
            'width' => 1600,
            'height' => 1000,
            'alt' => fake()->sentence(4),
            'folder' => 'Site',
        ];
    }
}
