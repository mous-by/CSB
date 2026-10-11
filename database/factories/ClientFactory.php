<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'organization' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2237'.fake()->unique()->numerify('#######'),
            'password' => 'Client-Pass-1',
            'is_active' => true,
        ];
    }
}
