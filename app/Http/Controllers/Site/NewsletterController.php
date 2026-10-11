<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Abonne;
use App\Services\Newsletter\NewsletterService;
use App\Support\Seo;
use App\Support\SpamGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inscription aux actualités (pied de page, AJAX) et désinscription en un clic depuis un e-mail.
 */
class NewsletterController extends Controller
{
    public function __construct(private readonly NewsletterService $service) {}

    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']], ['email.required' => 'Indiquez votre adresse e-mail.', 'email.email' => 'Cette adresse e-mail n\'est pas valide.'], ['email' => 'e-mail']);

        if (! SpamGuard::isSpam($request)) {
            $this->service->subscribe($data['email'], null, 'site', $request->ip());
        }

        $message = 'Merci ! Vous recevrez nos prochaines actualités.';

        return $request->expectsJson()
            ? response()->json(['title' => 'Inscription confirmée', 'message' => $message])
            : back()->with('status', $message);
    }

    public function unsubscribe(string $token): View
    {
        $abonne = Abonne::firstWhere('token', $token);
        if ($abonne) {
            $this->service->unsubscribe($abonne);
        }

        return view('site.pages.message', [
            'title' => $abonne ? 'Désinscription confirmée' : 'Lien inconnu',
            'message' => $abonne ? 'Vous ne recevrez plus nos actualités. Vous pouvez vous réinscrire à tout moment depuis le bas de chaque page.' : 'Ce lien de désinscription n\'est pas (ou plus) valide.',
            'seo' => Seo::make('Désinscription')->noIndex(),
        ]);
    }
}
