<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Support\PhoneNumber;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Espace client : tableau de bord, demandes, documents (téléchargement réservé au client), profil et mot de passe.
 * Un client ne voit jamais que ce qui lui appartient.
 */
class AccountController extends Controller
{
    public function home(Request $request): View
    {
        $client = $this->client($request);

        return view('client.home', [
            'client' => $client,
            'demandes' => $client->demandes()->latest('id')->limit(5)->get(),
            'documents' => $client->documents()->limit(5)->get(),
            'counts' => [
                'demandes' => $client->demandes()->count(),
                'ouvertes' => $client->demandes()->open()->count(),
                'documents' => $client->documents()->count(),
            ],
            'extras' => app(ClientAreaSections::class)->for($client),
            'seo' => Seo::make('Mon espace client')->noIndex(),
        ]);
    }

    public function demandes(Request $request): View
    {
        return view('client.demandes', [
            'demandes' => $this->client($request)->demandes()->latest('id')->paginate(15),
            'seo' => Seo::make('Mes demandes')->noIndex(),
        ]);
    }

    public function documents(Request $request): View
    {
        return view('client.documents', [
            'documents' => $this->client($request)->documents()->paginate(20),
            'seo' => Seo::make('Mes documents')->noIndex(),
        ]);
    }

    public function download(Request $request, ClientDocument $document): StreamedResponse
    {
        abort_unless($document->client_id === $this->client($request)->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        if ($document->first_downloaded_at === null) {
            $document->forceFill(['first_downloaded_at' => now()])->saveQuietly();
        }

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function profile(Request $request): View
    {
        return view('client.profile', ['client' => $this->client($request), 'seo' => Seo::make('Mon profil')->noIndex()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $client = $this->client($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', function ($attribute, $value, $fail) use ($client) {
                $normalized = PhoneNumber::normalize($value);
                if ($normalized === null) {
                    $fail('Ce numéro de téléphone n\'est pas valide.');
                } elseif (Client::withTrashed()->where('phone', $normalized)->whereKeyNot($client->id)->exists()) {
                    $fail('Ce numéro est déjà utilisé par un autre compte.');
                }
            }],
            'address' => ['nullable', 'string', 'max:300'],
            'city' => ['nullable', 'string', 'max:100'],
        ], [], ['name' => 'nom complet', 'organization' => 'organisation', 'phone' => 'téléphone', 'address' => 'adresse', 'city' => 'ville']);

        $client->update(['phone' => PhoneNumber::normalize($data['phone'] ?? null)] + $data);

        return back()->with('status', 'Profil enregistré.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $client = $this->client($request);
        $data = $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($client) {
                if (! Hash::check((string) $value, $client->password)) {
                    $fail('Le mot de passe actuel est incorrect.');
                }
            }],
            'password' => ['required', 'confirmed', ...Client::passwordRules(), Rule::notIn([$request->input('current_password')])],
        ], ['password.not_in' => 'Choisissez un mot de passe différent de l\'actuel.'], ['password' => 'nouveau mot de passe']);

        $client->forceFill(['password' => $data['password']])->save();

        return back()->with('status', 'Mot de passe modifié.');
    }

    private function client(Request $request): Client
    {
        return $request->user('client');
    }
}
