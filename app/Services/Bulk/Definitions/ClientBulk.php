<?php

namespace App\Services\Bulk\Definitions;

use App\Models\Client;
use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use App\Services\Clients\ClientAdminService;
use Illuminate\Database\Eloquent\Model;

/** Comptes clients en lot (fichier clients existant) ; chaque client reçoit un lien pour choisir son mot de passe. */
class ClientBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'clients';
    }

    public function label(): string
    {
        return 'clients';
    }

    public function icon(): string
    {
        return 'bx bx-user-pin';
    }

    public function indexRoute(): string
    {
        return 'clients.index';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Nom complet', 'required' => true, 'rules' => ['string', 'max:150'], 'width' => 28],
            'organization' => ['label' => 'Organisation', 'rules' => ['nullable', 'string', 'max:150'], 'width' => 28],
            'email' => ['label' => 'E-mail', 'required' => true, 'rules' => ['email', 'max:190', 'unique:clients,email'], 'unique' => true, 'width' => 30],
            'phone' => ['label' => 'Téléphone', 'rules' => ['nullable', 'string', 'max:30'], 'width' => 16],
            'city' => ['label' => 'Ville', 'rules' => ['nullable', 'string', 'max:100'], 'width' => 14],
            'invite' => ['label' => 'Envoyer l\'invitation', 'type' => 'bool', 'default' => true, 'width' => 12, 'help' => 'Oui : le client reçoit un lien pour choisir son mot de passe.'],
        ];
    }

    public function create(array $row, User $user): Model
    {
        $invite = (bool) ($row['invite'] ?? true);
        unset($row['invite']);

        return app(ClientAdminService::class)->create($row, $user, $invite);
    }
}
