<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\Clients\ClientAccountService;
use App\Support\PhoneNumber;
use App\Support\Seo;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Accès à l'espace client : connexion (e-mail ou téléphone), inscription, mot de passe oublié, confirmation de
 * l'adresse e-mail, déconnexion. Garde `client`, distincte de l'administration.
 */
class AuthController extends Controller
{
    public function __construct(private readonly ClientAccountService $accounts) {}

    public function login(): View
    {
        return view('client.auth.login', ['seo' => Seo::make('Espace client — connexion')->noIndex()]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:190'], 'password' => ['required', 'string', 'max:255']], [], ['login' => 'e-mail ou téléphone', 'password' => 'mot de passe']);
        $client = $this->accounts->attempt($data['login'], $data['password'], (string) $request->ip());

        Auth::guard('client')->login($client, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('client.home'));
    }

    public function register(): View
    {
        return view('client.auth.register', ['seo' => Seo::make('Créer mon espace client')->noIndex()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('clients', 'email')],
            'phone' => ['nullable', 'string', 'max:30', function ($attribute, $value, $fail) {
                $normalized = PhoneNumber::normalize($value);
                if ($normalized === null) {
                    $fail('Ce numéro de téléphone n\'est pas valide (ex. 74 74 56 69).');
                } elseif (Client::withTrashed()->where('phone', $normalized)->exists()) {
                    $fail('Ce numéro est déjà utilisé par un autre compte.');
                }
            }],
            'password' => ['required', 'confirmed', ...Client::passwordRules()],
            'consent' => ['accepted'],
        ], ['email.unique' => 'Un compte existe déjà avec cette adresse : connectez-vous ou utilisez « Mot de passe oublié ».', 'consent.accepted' => 'Merci d\'accepter la politique de confidentialité.'],
            ['name' => 'nom complet', 'organization' => 'organisation', 'email' => 'e-mail', 'phone' => 'téléphone', 'password' => 'mot de passe']);

        $client = $this->accounts->register($data);
        Auth::guard('client')->login($client);
        $request->session()->regenerate();

        return redirect()->route('client.home')->with('status', 'Bienvenue ! Confirmez votre adresse e-mail grâce au lien que nous venons de vous envoyer pour retrouver vos demandes.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('site.home');
    }

    public function forgot(): View
    {
        return view('client.auth.forgot', ['seo' => Seo::make('Mot de passe oublié')->noIndex()]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [], ['email' => 'e-mail']);
        Password::broker('clients')->sendResetLink(['email' => mb_strtolower($data['email'])]);

        // Même message que le compte existe ou non (aucune information sur les comptes).
        return back()->with('status', 'Si un compte correspond à cette adresse, un lien de réinitialisation vient d\'y être envoyé.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('client.auth.reset', ['token' => $token, 'email' => (string) $request->query('email'), 'seo' => Seo::make('Nouveau mot de passe')->noIndex()]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', ...Client::passwordRules()],
        ], [], ['password' => 'mot de passe']);

        $status = Password::broker('clients')->reset($data, function (Client $client, string $password) {
            $client->forceFill(['password' => $password])->setRememberToken(null);
            $client->save();
            event(new PasswordReset($client));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('client.login')->with('status', 'Mot de passe modifié : vous pouvez vous connecter.')
            : back()->withErrors(['email' => 'Ce lien n\'est plus valide : demandez-en un nouveau.']);
    }

    public function verify(Request $request, Client $client, string $hash): RedirectResponse
    {
        abort_unless(hash_equals(sha1($client->email), $hash), 403);
        $linked = $this->accounts->verify($client);

        return redirect()->route(Auth::guard('client')->check() ? 'client.home' : 'client.login')
            ->with('status', 'Adresse e-mail confirmée.'.($linked ? " {$linked} demande(s) retrouvée(s) et ajoutée(s) à votre espace." : ''));
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $client = $request->user('client');
        if ($client->hasVerifiedEmail()) {
            return back()->with('status', 'Votre adresse est déjà confirmée.');
        }

        return $this->accounts->sendVerification($client)
            ? back()->with('status', 'Un nouveau lien de confirmation vient de vous être envoyé.')
            : back()->with('error', 'L\'envoi de l\'e-mail a échoué. Réessayez plus tard ou contactez CSB.');
    }
}
