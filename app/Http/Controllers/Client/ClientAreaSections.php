<?php

namespace App\Http\Controllers\Client;

use App\Models\Client;

/**
 * Blocs du tableau de bord client fournis par les autres modules (commandes, tickets). Chaque module enregistre une
 * fonction ; le tableau de bord les affiche s'ils ont du contenu.
 */
class ClientAreaSections
{
    /** @var list<callable(Client): ?array{title: string, icon: string, route: string, count: int, open: int, label: string}> */
    private array $providers = [];

    public function register(callable $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return list<array{title: string, icon: string, route: string, count: int, open: int, label: string}> */
    public function for(Client $client): array
    {
        return array_values(array_filter(array_map(fn ($provider) => $provider($client), $this->providers)));
    }
}
