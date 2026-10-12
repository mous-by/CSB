<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Temoignage;
use App\Services\Temoignages\TemoignageService;
use App\Support\Seo;
use App\Support\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Page publique des témoignages (vidéos d'abord) et formulaire « Partager mon expérience ». */
class TemoignageController extends Controller
{
    public function index(): View
    {
        $temoignages = Temoignage::with('photo')->published()->orderByDesc('is_featured')->ordered()->get();

        return view('site.temoignages.index', [
            'videos' => $temoignages->filter->isVideo()->values(),
            'ecrits' => $temoignages->reject->isVideo()->filter(fn ($t) => filled($t->content))->values(),
            'seo' => Seo::make('Témoignages de nos clients', 'Ils ont choisi Computer Service Barry : témoignages en vidéo et par écrit de nos clients, écoles, commerces et entreprises.'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('site.temoignages.create', [
            'client' => $request->user('client'),
            'seo' => Seo::make('Partager mon expérience', 'Racontez votre expérience avec CSB, en vidéo ou par écrit.')->noIndex(),
        ]);
    }

    public function store(Request $request, TemoignageService $service): RedirectResponse
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:150'],
            'author_title' => ['nullable', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'contact' => ['required', 'string', 'max:150'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'content' => ['nullable', 'string', 'max:1500', 'required_without:video_token'],
            'video_token' => ['nullable', 'string'],
            'photo_upload' => ['nullable', 'image', 'max:5120'],
            'consent' => ['accepted'],
        ], [
            'content.required_without' => 'Écrivez votre témoignage ou envoyez une vidéo.',
            'consent.accepted' => 'Votre accord est nécessaire pour que nous puissions publier votre témoignage.',
        ], ['author_name' => 'nom', 'author_title' => 'fonction', 'organization' => 'entreprise ou établissement', 'contact' => 'téléphone ou e-mail', 'content' => 'témoignage', 'photo_upload' => 'photo']);

        if (! SpamGuard::isSpam($request)) {
            $service->submit($data, $request->file('photo_upload'), $request->user('client'));
        }

        return redirect()->route('site.temoignages.create')->with('temoignage_sent', true);
    }
}
