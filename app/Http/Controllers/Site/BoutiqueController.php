<?php

namespace App\Http\Controllers\Site;

use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Materiel;
use App\Services\Boutique\CartService;
use App\Services\Boutique\CommandeService;
use App\Support\PhoneNumber;
use App\Support\Seo;
use App\Support\SpamGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Boutique (cahier §15.3) : panier en session (ajout, quantité et retrait en AJAX, sans JavaScript par formulaires),
 * commande sans compte (ou préremplie pour un client connecté), page de suivi accessible par un lien secret.
 */
class BoutiqueController extends Controller
{
    public function __construct(private readonly CartService $cart, private readonly CommandeService $commandes) {}

    public function panier(): View
    {
        $lines = $this->cart->lines();

        return view('site.boutique.panier', ['lines' => $lines, 'total' => $this->cart->total($lines), 'seo' => Seo::make('Mon panier')->noIndex()]);
    }

    public function add(Request $request, Materiel $materiel): JsonResponse|RedirectResponse
    {
        abort_unless(CartService::purchasable($materiel), 404);
        $this->cart->add($materiel, (int) $request->input('quantity', 1));

        return $this->cartResponse($request, "« {$materiel->designation} » ajouté au panier.");
    }

    public function update(Request $request, Materiel $materiel): JsonResponse|RedirectResponse
    {
        $this->cart->set($materiel, (int) $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_QUANTITY]])['quantity']);

        return $this->cartResponse($request, 'Panier mis à jour.');
    }

    public function remove(Request $request, Materiel $materiel): JsonResponse|RedirectResponse
    {
        $this->cart->set($materiel, 0);

        return $this->cartResponse($request, 'Article retiré du panier.');
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('site.panier')->with('error', 'Votre panier est vide.');
        }

        return view('site.boutique.commande', [
            'lines' => $lines, 'total' => $this->cart->total($lines), 'client' => $request->user('client'),
            'seo' => Seo::make('Finaliser ma commande')->noIndex(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'phone' => ['required', 'string', 'max:30', fn ($a, $v, $fail) => PhoneNumber::normalize((string) $v) ? null : $fail('Numéro de téléphone invalide (ex. 70 00 00 00).')],
            'delivery_method' => ['required', Rule::enum(DeliveryMethod::class)],
            'address' => ['nullable', 'required_if:delivery_method,livraison', 'string', 'max:300'],
            'city' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ], ['address.required_if' => 'Indiquez l\'adresse de livraison.', 'consent.accepted' => 'Merci d\'accepter l\'utilisation de vos informations pour traiter la commande.'], [
            'name' => 'nom', 'organization' => 'entreprise', 'email' => 'e-mail', 'phone' => 'téléphone', 'delivery_method' => 'mode de réception',
            'address' => 'adresse', 'city' => 'ville', 'payment_method' => 'moyen de paiement', 'notes' => 'remarques',
        ]);

        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('site.panier')->with('error', 'Votre panier est vide.');
        }
        // Robot : fausse réussite, rien n'est enregistré (comme les demandes).
        if (SpamGuard::isSpam($request)) {
            $this->cart->clear();

            return redirect()->route('site.home');
        }

        $commande = $this->commandes->place($data, $lines, $request->user('client'), $request->ip());
        $this->cart->clear();

        return redirect()->route('site.commande.suivi', [$commande->number, $commande->access_token])->with('commande_placed', true);
    }

    public function suivi(string $number, string $token): View
    {
        $commande = Commande::with('lignes.materiel.mainImage')->where('number', $number)->firstOrFail();
        abort_unless(hash_equals($commande->access_token, $token), 404);

        return view('site.boutique.suivi', [
            'commande' => $commande, 'instructions' => $this->commandes->paymentInstructions($commande),
            'seo' => Seo::make('Commande '.$commande->number)->noIndex(),
        ]);
    }

    private function cartResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            $lines = $this->cart->lines();

            return response()->json([
                'message' => $message, 'count' => $this->cart->count(), 'total' => Materiel::money($this->cart->total($lines)),
                'lines' => $lines->mapWithKeys(fn ($l) => [$l['materiel']->id => ['quantity' => $l['quantity'], 'total' => Materiel::money($l['line_total'])]]),
            ]);
        }

        return back()->with('status', $message);
    }
}
