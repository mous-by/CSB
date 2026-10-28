<?php

namespace App\Http\Controllers\Site;

use App\Enums\AdvisorBudget;
use App\Enums\AdvisorForm;
use App\Enums\AdvisorProfile;
use App\Enums\AdvisorUsage;
use App\Http\Controllers\Controller;
use App\Services\Conseiller\ComputerAdvisor;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Choisir mon ordinateur » : questions simples (métier, usages, format, budget, état, niveau), puis les ordinateurs
 * du catalogue qui conviennent et les conseils pour un débutant. Les réponses passent dans l'adresse (GET) : la page
 * se partage et fonctionne sans JavaScript ; site.js affiche une question à la fois.
 */
class ConseillerController extends Controller
{
    public function __invoke(Request $request, ComputerAdvisor $advisor): View
    {
        $answers = [
            'profil' => AdvisorProfile::tryFrom((string) $request->query('profil')),
            'usages' => collect((array) $request->query('usages', []))->map(fn ($u) => AdvisorUsage::tryFrom((string) $u))->filter()->values()->all(),
            'format' => AdvisorForm::tryFrom((string) $request->query('format')),
            'budget' => AdvisorBudget::tryFrom((string) $request->query('budget')) ?? AdvisorBudget::Inconnu,
            'etat' => $request->query('etat') === 'neuf' ? 'neuf' : 'tous',
            'niveau' => in_array($request->query('niveau'), ['debutant', 'moyen', 'avance'], true) ? $request->query('niveau') : 'debutant',
        ];
        $submitted = $request->has('profil');
        $seo = Seo::make('Choisir mon ordinateur', 'Répondez à quelques questions simples : CSB vous propose les ordinateurs adaptés à votre métier et à votre budget.');

        return view('site.conseiller.index', [
            'answers' => $answers,
            'submitted' => $submitted,
            'result' => $submitted ? $advisor->recommend($answers['profil'], $answers['usages'], $answers['format'], $answers['budget'], $answers['etat'] === 'neuf') : null,
            'seo' => $submitted ? $seo->noIndex() : $seo,
        ]);
    }
}
