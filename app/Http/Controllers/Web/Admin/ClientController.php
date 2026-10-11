<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\DocumentType;
use App\Http\Controllers\Client\ClientAreaSections;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Services\Clients\ClientAdminService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Clients (espace client) : liste, fiche (demandes, documents, activité), création avec invitation, modification,
 * activation, lien de mot de passe, documents déposés dans l'espace du client.
 */
class ClientController extends Controller
{
    public function __construct(private readonly ClientAdminService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Client::class);

        return view('clients.index', ['clients' => Client::withCount(['demandes', 'documents'])->latest('id')->get()]);
    }

    public function show(Client $client): View
    {
        Gate::authorize('view', $client);

        return view('clients.show', [
            'client' => $client->load(['documents', 'demandes' => fn ($q) => $q->latest('id')]),
            'extras' => app(ClientAreaSections::class)->for($client),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Client::class);
        $data = $this->validated($request, null);
        $client = $this->service->create($data, $request->user(), $request->boolean('invite', true));

        return redirect()->route('clients.show', $client)->with('status', "Client « {$client->name} » créé".($request->boolean('invite', true) ? ' : un lien pour choisir son mot de passe lui a été envoyé.' : '.'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);
        $data = $this->validated($request, $client);
        $client->update(['phone' => PhoneNumber::normalize($data['phone'] ?? null), 'is_active' => $request->boolean('is_active')] + $data);

        return redirect()->route('clients.show', $client)->with('status', 'Client enregistré.');
    }

    public function sendPasswordLink(Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        return $this->service->sendPasswordLink($client)
            ? back()->with('status', "Lien envoyé à {$client->email}.")
            : back()->with('error', 'L\'envoi de l\'e-mail a échoué (vérifiez la configuration SMTP).');
    }

    public function addDocument(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,odt,ods,jpg,jpeg,png,zip', 'max:10240'],
        ], ['file.mimes' => 'Document accepté : PDF, Word, Excel, OpenDocument, image ou ZIP.', 'file.max' => '10 Mo au plus.'], ['title' => 'titre', 'file' => 'fichier']);

        $this->service->addDocument($client, $request->file('file'), $data['title'], DocumentType::from($data['type']), $request->user());

        return back()->with('status', 'Document déposé dans l\'espace du client.');
    }

    public function downloadDocument(Client $client, ClientDocument $document): StreamedResponse
    {
        abort_unless($document->client_id === $client->id && Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function removeDocument(Client $client, ClientDocument $document): RedirectResponse
    {
        abort_unless($document->client_id === $client->id, 404);
        $this->service->removeDocument($document);

        return back()->with('status', 'Document retiré de l\'espace du client.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        Gate::authorize('delete', $client);
        $client->delete();

        return redirect()->route('clients.index')->with('status', "Client « {$client->name} » supprimé.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Client $client): array
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('clients', 'email')->ignore($client)],
            'phone' => ['nullable', 'string', 'max:30', function ($attribute, $value, $fail) use ($client) {
                $normalized = PhoneNumber::normalize($value);
                if ($normalized === null) {
                    $fail('Numéro de téléphone invalide.');
                } elseif (Client::withTrashed()->where('phone', $normalized)->when($client, fn ($q) => $q->whereKeyNot($client->id))->exists()) {
                    $fail('Ce numéro est déjà utilisé par un autre client.');
                }
            }],
            'address' => ['nullable', 'string', 'max:300'],
            'city' => ['nullable', 'string', 'max:100'],
        ], ['email.unique' => 'Un client utilise déjà cette adresse.'], ['name' => 'nom', 'organization' => 'organisation', 'phone' => 'téléphone', 'address' => 'adresse', 'city' => 'ville']);
    }
}
