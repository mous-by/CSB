<?php

namespace App\Http\Controllers\Site;

use App\Enums\LogicielStatus;
use App\Enums\NeedType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\DemandeRequest;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Services\Demandes\DemandeService;
use App\Support\Seo;
use App\Support\SiteSettings;
use App\Support\SpamGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Formulaires commerciaux du site (cahier §6) : contact, devis, démonstration (fiche logiciel préremplie) et
 * prix / disponibilité (fiche matériel préremplie). Envoi en AJAX (réponse JSON) ou classique (redirection) ; un
 * robot reçoit la même réponse qu'un visiteur mais rien n'est enregistré (SpamGuard).
 */
class DemandeController extends Controller
{
    public function __construct(private readonly DemandeService $service, private readonly SiteSettings $settings) {}

    public function contact(): View
    {
        return view('site.demandes.contact', [
            'seo' => Seo::make('Contact', 'Contactez '.$this->settings->get('company.name').' par téléphone, WhatsApp, e-mail ou grâce au formulaire : nous vous répondons rapidement.'),
        ]);
    }

    public function devis(Request $request): View
    {
        $logiciel = Logiciel::published()->where('slug', (string) $request->query('logiciel'))->first();
        $materiel = Materiel::published()->where('slug', (string) $request->query('materiel'))->first();
        $need = NeedType::tryFrom((string) $request->query('type')) ?? ($logiciel ? NeedType::Logiciel : ($materiel ? NeedType::Materiel : null));

        return view('site.demandes.devis', [
            'subjectModel' => $logiciel ?? $materiel,
            'need' => $need,
            'besoin' => mb_substr((string) $request->query('besoin', ''), 0, 150),
            'seo' => Seo::make('Demander un devis', 'Décrivez votre besoin (logiciel, développement sur mesure, matériel, maintenance) : CSB vous adresse une proposition adaptée.')->noIndex(),
        ]);
    }

    public function demonstration(Logiciel $logiciel): View
    {
        $this->ensureDemonstrable($logiciel);

        return view('site.demandes.demonstration', [
            'logiciel' => $logiciel->load(['logo', 'gallery']),
            'seo' => Seo::make('Démonstration de '.$logiciel->name, 'Demandez une démonstration de '.$logiciel->name.' : CSB vous présente le logiciel selon vos disponibilités.')->noIndex(),
        ]);
    }

    public function disponibilite(Materiel $materiel): View
    {
        abort_unless($materiel->is_published, 404);

        return view('site.demandes.disponibilite', [
            'materiel' => $materiel->load(['mainImage', 'gallery']),
            'seo' => Seo::make(($materiel->price === null ? 'Prix et disponibilité — ' : 'Disponibilité — ').$materiel->designation)->noIndex(),
        ]);
    }

    public function storeContact(DemandeRequest $request): JsonResponse|RedirectResponse
    {
        return $this->store($request, null, 'Message envoyé', 'Merci ! Votre message a bien été reçu : nous vous répondrons dans les meilleurs délais.');
    }

    public function storeDevis(DemandeRequest $request): JsonResponse|RedirectResponse
    {
        $subject = Logiciel::published()->where('slug', (string) $request->input('logiciel'))->first()
            ?? Materiel::published()->where('slug', (string) $request->input('materiel'))->first();

        return $this->store($request, $subject, 'Demande de devis envoyée', 'Merci ! Votre demande de devis a bien été reçue : nous l\'étudions et revenons vers vous rapidement avec une proposition.');
    }

    public function storeDemonstration(DemandeRequest $request, Logiciel $logiciel): JsonResponse|RedirectResponse
    {
        $this->ensureDemonstrable($logiciel);

        return $this->store($request, $logiciel, 'Demande de démonstration envoyée', "Merci ! Nous vous contactons rapidement pour organiser la démonstration de {$logiciel->name}.");
    }

    public function storeDisponibilite(DemandeRequest $request, Materiel $materiel): JsonResponse|RedirectResponse
    {
        abort_unless($materiel->is_published, 404);

        return $this->store($request, $materiel, 'Demande envoyée', "Merci ! Nous vérifions la disponibilité et le prix de « {$materiel->designation} » et revenons vers vous rapidement.");
    }

    private function store(DemandeRequest $request, ?Model $subject, string $title, string $message): JsonResponse|RedirectResponse
    {
        if (! SpamGuard::isSpam($request)) {
            $this->service->receive($request->demandeType(), $request->validated(), $request, $subject, $request->file('attachment'));
        }

        if ($request->expectsJson()) {
            return response()->json(['title' => $title, 'message' => $message]);
        }

        return back()->with('demande_sent', ['title' => $title, 'message' => $message]);
    }

    private function ensureDemonstrable(Logiciel $logiciel): void
    {
        abort_unless($logiciel->is_published && $logiciel->status !== LogicielStatus::Archive, 404);
    }
}
