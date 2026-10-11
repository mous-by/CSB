<?php

namespace Database\Factories;

use App\Enums\DemandeStatus;
use App\Enums\DemandeType;
use App\Models\Demande;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Demande>
 */
class DemandeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => DemandeType::Contact,
            'status' => DemandeStatus::Nouvelle,
            'name' => fake()->name(),
            'organization' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => '70 '.fake()->numerify('## ## ##'),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'consent_at' => now(),
        ];
    }

    public function of(DemandeType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
