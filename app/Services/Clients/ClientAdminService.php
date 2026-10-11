<?php

namespace App\Services\Clients;

use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Gestion des clients par l'équipe : création (le client choisit son mot de passe par le lien reçu), lien de
 * réinitialisation, documents déposés dans son espace (disque privé), tout journalisé.
 */
class ClientAdminService
{
    public const DOCUMENT_DIRECTORY = 'clients';

    public function __construct(private readonly ActivityLogger $logger) {}

    /** @param array{name: string, organization?: ?string, email: string, phone?: ?string, address?: ?string, city?: ?string} $data */
    public function create(array $data, User $actor, bool $invite = true): Client
    {
        $client = Client::create([
            'name' => $data['name'], 'organization' => $data['organization'] ?? null, 'email' => $data['email'],
            'phone' => PhoneNumber::normalize($data['phone'] ?? null), 'address' => $data['address'] ?? null, 'city' => $data['city'] ?? null,
            // Mot de passe aléatoire inconnu de tous : le client choisit le sien par le lien d'invitation.
            'password' => Str::password(32), 'email_verified_at' => now(),
        ]);

        if ($invite) {
            $this->sendPasswordLink($client);
        }

        return $client;
    }

    public function sendPasswordLink(Client $client): bool
    {
        return rescue(fn () => Password::broker('clients')->sendResetLink(['email' => $client->email]) === Password::RESET_LINK_SENT, false, false);
    }

    public function addDocument(Client $client, UploadedFile $file, string $title, DocumentType $type, User $actor): ClientDocument
    {
        $path = $file->storeAs(self::DOCUMENT_DIRECTORY.'/'.$client->id, Str::uuid().'.'.$file->extension(), 'local');

        return DB::transaction(function () use ($client, $file, $title, $type, $actor, $path) {
            $document = $client->documents()->create([
                'title' => $title, 'type' => $type, 'path' => $path, 'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'size' => $file->getSize(), 'uploaded_by' => $actor->id,
            ]);
            $this->logger->log('clients.document_added', 'clients', "Document déposé — {$client->name} : {$title}", $client, new: ['document' => $title, 'type' => $type->value], actor: $actor);

            return $document;
        });
    }

    public function removeDocument(ClientDocument $document): void
    {
        $document->delete();
    }
}
