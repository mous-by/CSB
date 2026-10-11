<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\Demande;
use App\Notifications\ClientVerifyEmail;
use App\Services\Audit\ActivityLogger;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Comptes de l'espace client : inscription, connexion (e-mail ou téléphone, 5 tentatives), confirmation de l'adresse
 * e-mail (qui rattache au compte les demandes faites auparavant avec cette adresse).
 */
class ClientAccountService
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly ActivityLogger $logger) {}

    /** @param array{name: string, organization?: ?string, email: string, phone?: ?string, password: string} $data */
    public function register(array $data): Client
    {
        $client = DB::transaction(function () use ($data) {
            $client = Client::create([
                'name' => $data['name'], 'organization' => $data['organization'] ?? null, 'email' => $data['email'],
                'phone' => PhoneNumber::normalize($data['phone'] ?? null), 'password' => $data['password'],
            ]);
            $this->logger->log('clients.registered', 'clients', "Inscription à l'espace client — {$client->name}", $client);

            return $client;
        });

        $this->sendVerification($client);

        return $client;
    }

    /** E-mail de confirmation ; un échec d'envoi n'empêche pas l'inscription (le client peut le redemander). */
    public function sendVerification(Client $client): bool
    {
        try {
            $client->notify(new ClientVerifyEmail);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** Confirme l'adresse et rattache les demandes antérieures faites avec elle. */
    public function verify(Client $client): int
    {
        return DB::transaction(function () use ($client) {
            if (! $client->hasVerifiedEmail()) {
                $client->forceFill(['email_verified_at' => now()])->save();
            }

            $linked = Demande::whereNull('client_id')->where('email', $client->email)->get()
                ->each(fn (Demande $demande) => $demande->update(['client_id' => $client->id]))->count();

            $this->logger->log('clients.verified', 'clients', "Adresse e-mail confirmée — {$client->name} ({$linked} demande(s) rattachée(s))", $client, actor: null);

            return $linked;
        });
    }

    /**
     * @throws ValidationException
     */
    public function attempt(string $login, string $password, string $ip): Client
    {
        $key = 'client-login|'.Str::lower(trim($login)).'|'.$ip;
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages(['login' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($key).' secondes.'])->status(429);
        }

        $phone = PhoneNumber::normalize($login);
        $client = Client::query()
            ->where(fn ($q) => $q->where('email', mb_strtolower(trim($login)))->when($phone, fn ($q) => $q->orWhere('phone', $phone)))
            ->first();

        if (! $client || ! Hash::check($password, $client->password)) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['login' => 'Identifiants incorrects.']);
        }
        if (! $client->is_active) {
            throw ValidationException::withMessages(['login' => 'Ce compte est désactivé. Contactez CSB.']);
        }

        RateLimiter::clear($key);
        $client->forceFill(['last_login_at' => now()])->saveQuietly();

        return $client;
    }
}
