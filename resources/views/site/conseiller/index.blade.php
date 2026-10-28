@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, j\'aimerais être conseillé pour choisir un ordinateur.')

@php
    $levels = [1 => 'un ordinateur d\'entrée de gamme suffit', 2 => 'il faut un ordinateur de milieu de gamme', 3 => 'il faut un ordinateur puissant'];
    $usageValues = array_map(fn ($u) => $u->value, $answers['usages']);
    $whatsapp = $site->whatsappLink('Bonjour CSB, j\'ai utilisé « Choisir mon ordinateur » sur votre site et j\'aimerais un conseil.');
@endphp

@section('content')
    @include('site.partials.page-hero', [
        'title' => 'Choisir mon ordinateur',
        'crumbs' => [['Matériel', route('site.materiels.index')]],
        'intro' => 'Vous ne savez pas quel ordinateur acheter ? Répondez à 6 questions simples : nous vous proposons les modèles adaptés à votre travail et à votre budget, et nous vous expliquons pourquoi.',
    ])

    @if ($submitted)
        @php
            $budgetText = $answers['budget'] === \App\Enums\AdvisorBudget::Inconnu ? null : $answers['budget']->label();
            $need = collect($result['usages'])->map->label()->implode(', ');
            $devisText = mb_substr('Ordinateur pour : '.$need.($answers['format'] ? ' — '.$answers['format']->label() : '').($budgetText ? ' — budget '.$budgetText : ''), 0, 150);
        @endphp
        <section class="section-sm" id="resultats" aria-labelledby="resultats-titre">
            <div class="container">
                <div class="advisor-summary reveal">
                    <div>
                        <span class="eyebrow-inline"><i class='bx bx-check-shield' aria-hidden="true"></i> Votre besoin</span>
                        <h2 id="resultats-titre" class="h3 mb-2">{{ $answers['profil']?->label() ?? 'Votre profil' }} : {{ $levels[$result['level']] }}</h2>
                        <p class="mb-0">Usages : <strong>{{ $need }}</strong>@if ($answers['format']) · {{ $answers['format']->label() }}@endif @if ($budgetText) · budget {{ $budgetText }}@endif {{ $answers['etat'] === 'neuf' ? ' · neuf seulement' : '' }}.</p>
                    </div>
                    <a href="#questions" class="btn btn-outline-primary text-nowrap"><i class='bx bx-edit' aria-hidden="true"></i> Modifier mes réponses</a>
                </div>

                <div class="row g-4 mt-1">
                    <div class="{{ $answers['niveau'] === 'avance' ? 'col-12' : 'col-lg-8' }}">
                        <h3 class="h5 mb-3">Ce qu'il vous faut au minimum</h3>
                        <ul class="advisor-advice">
                            @foreach ($result['usages'] as $usage)
                                <li><i class='bx {{ $usage->icon() }}' aria-hidden="true"></i><div><strong>{{ $usage->label() }}</strong><br>{{ $usage->advice() }}</div></li>
                            @endforeach
                            @if ($answers['format'])
                                <li><i class='bx bx-laptop' aria-hidden="true"></i><div><strong>{{ $answers['format']->label() }}</strong><br>{{ $answers['format']->hint() }}</div></li>
                            @endif
                        </ul>
                    </div>
                    @if ($answers['niveau'] !== 'avance')
                        <div class="col-lg-4">
                            <div class="advisor-glossary">
                                <h3 class="h6"><i class='bx bx-bulb' aria-hidden="true"></i> Les mots à connaître</h3>
                                <dl class="mb-0">
                                    <dt>Mémoire (RAM)</dt><dd>Permet d'ouvrir plusieurs logiciels en même temps sans ralentir. Plus il y en a, mieux c'est.</dd>
                                    <dt>Disque SSD</dt><dd>Là où sont rangés vos fichiers. Un SSD est bien plus rapide qu'un ancien disque dur (HDD).</dd>
                                    <dt>Processeur</dt><dd>Le « moteur » de l'ordinateur : Intel Core i3 &lt; i5 &lt; i7, ou AMD Ryzen 3 &lt; 5 &lt; 7.</dd>
                                    <dt>Reconditionné</dt><dd>Ordinateur déjà utilisé, vérifié et remis en état : bien moins cher qu'un neuf.</dd>
                                </dl>
                            </div>
                        </div>
                    @endif
                </div>

                <h3 class="h5 mt-5 mb-3">{{ $result['matches']->isEmpty() ? 'Aucun modèle de notre catalogue ne correspond pour le moment' : 'Nos ordinateurs qui vous conviennent' }}</h3>
                @if ($result['matches']->isEmpty())
                    <div class="empty-state">
                        <i class='bx bx-search-alt' aria-hidden="true"></i>
                        <p class="mt-3 mb-2">
                            @if ($result['cheapest_above'])
                                Dans notre catalogue, les ordinateurs adaptés à cet usage commencent à <strong>{{ \App\Models\Materiel::money($result['cheapest_above']) }}</strong>. Élargissez votre budget ou acceptez le reconditionné, ou laissez-nous chercher pour vous.
                            @else
                                Pas d'inquiétude : CSB trouve pour vous le bon modèle, au bon prix.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($result['matches'] as $index => $match)
                            <div class="col-sm-6 col-lg-4">
                                <div class="advisor-match {{ $index === 0 ? 'is-best' : '' }}">
                                    @if ($index === 0)<span class="advisor-best"><i class='bx bxs-star' aria-hidden="true"></i> Notre meilleur choix pour vous</span>@endif
                                    @include('site.materiels._card', ['materiel' => $match['materiel']])
                                    <ul class="advisor-reasons">
                                        @foreach ($match['reasons'] as $reason)
                                            <li class="ok"><i class='bx bx-check' aria-hidden="true"></i>{{ $reason }}</li>
                                        @endforeach
                                        @foreach ($match['warnings'] as $warning)
                                            <li class="warn"><i class='bx bx-info-circle' aria-hidden="true"></i>{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="advisor-help mt-4">
                    <div>
                        <strong>{{ $answers['niveau'] === 'debutant' ? 'Vous hésitez encore ? C\'est normal.' : 'Besoin d\'un avis ?' }}</strong>
                        Un conseiller CSB vous rappelle, gratuitement et sans engagement, pour confirmer le bon choix.
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if (Route::has('site.devis'))
                            <a href="{{ route('site.devis', ['type' => 'materiel', 'besoin' => $devisText]) }}" class="btn btn-accent">Être rappelé par CSB</a>
                        @endif
                        @if ($whatsapp)
                            <a href="{{ $whatsapp }}" class="btn btn-outline-primary" target="_blank" rel="noopener"><i class='bx bxl-whatsapp' aria-hidden="true"></i> WhatsApp</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    @php
        $showcaseHtml = trim(view('site.partials.showcase')->render());
    @endphp
    {{-- Questions posées devant les réalisations et matériels de CSB qui défilent. --}}
    <section class="section showcase-stage advisor-stage {{ $showcaseHtml === '' ? '' : 'has-showcase' }}" id="questions">
        {!! $showcaseHtml !!}
        <div class="container">
            <form method="GET" action="{{ route('site.conseiller') }}#resultats" class="advisor-wizard" data-wizard>
                <div class="advisor-progress" aria-hidden="true"><span data-wizard-progress></span></div>

                <fieldset class="advisor-step" data-step>
                    <legend><span class="advisor-step-num">1</span> Quel est votre métier ou votre situation ?</legend>
                    <div class="advisor-options">
                        @foreach (\App\Enums\AdvisorProfile::cases() as $profile)
                            <label class="advisor-option">
                                <input type="radio" name="profil" value="{{ $profile->value }}" @checked($answers['profil'] === $profile) required>
                                <span><i class='bx {{ $profile->icon() }}' aria-hidden="true"></i>{{ $profile->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="advisor-step" data-step>
                    <legend><span class="advisor-step-num">2</span> Que ferez-vous aussi avec l'ordinateur ?</legend>
                    <p class="advisor-hint">Facultatif : cochez ce qui s'ajoute à votre métier (plusieurs choix possibles).</p>
                    <div class="advisor-options">
                        @foreach (\App\Enums\AdvisorUsage::cases() as $usage)
                            <label class="advisor-option">
                                <input type="checkbox" name="usages[]" value="{{ $usage->value }}" @checked(in_array($usage->value, $usageValues, true))>
                                <span><i class='bx {{ $usage->icon() }}' aria-hidden="true"></i>{{ $usage->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="advisor-step" data-step>
                    <legend><span class="advisor-step-num">3</span> Portable ou ordinateur de bureau ?</legend>
                    <div class="advisor-options">
                        @foreach (\App\Enums\AdvisorForm::cases() as $form)
                            <label class="advisor-option advisor-option-wide">
                                <input type="radio" name="format" value="{{ $form->value }}" @checked($answers['format'] === $form)>
                                <span><strong>{{ $form->label() }}</strong><small>{{ $form->hint() }}</small></span>
                            </label>
                        @endforeach
                        <label class="advisor-option advisor-option-wide">
                            <input type="radio" name="format" value="" @checked($submitted && ! $answers['format'])>
                            <span><strong>Peu importe</strong><small>Montrez-moi les deux.</small></span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="advisor-step" data-step>
                    <legend><span class="advisor-step-num">4</span> Quel est votre budget ?</legend>
                    <div class="advisor-options">
                        @foreach (\App\Enums\AdvisorBudget::cases() as $budget)
                            <label class="advisor-option">
                                <input type="radio" name="budget" value="{{ $budget->value }}" @checked($submitted && $answers['budget'] === $budget)>
                                <span>{{ $budget->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="advisor-step" data-step>
                    <legend><span class="advisor-step-num">5</span> Neuf, ou reconditionné accepté ?</legend>
                    <div class="advisor-options">
                        <label class="advisor-option advisor-option-wide">
                            <input type="radio" name="etat" value="tous" @checked($answers['etat'] === 'tous')>
                            <span><strong>Reconditionné accepté</strong><small>Plus de choix et des prix plus bas : l'ordinateur est vérifié et remis en état.</small></span>
                        </label>
                        <label class="advisor-option advisor-option-wide">
                            <input type="radio" name="etat" value="neuf" @checked($answers['etat'] === 'neuf')>
                            <span><strong>Neuf seulement</strong><small>Jamais utilisé, avec la garantie du fabricant.</small></span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="advisor-step" data-step>
                    <legend><span class="advisor-step-num">6</span> Et vous, avec l'informatique ?</legend>
                    <div class="advisor-options">
                        @foreach (['debutant' => ['Je débute', 'Expliquez-moi avec des mots simples.'], 'moyen' => ['Je me débrouille', 'Je connais les bases.'], 'avance' => ['Je m\'y connais', 'Allez droit au but.']] as $value => [$levelLabel, $levelHint])
                            <label class="advisor-option advisor-option-wide">
                                <input type="radio" name="niveau" value="{{ $value }}" @checked($answers['niveau'] === $value)>
                                <span><strong>{{ $levelLabel }}</strong><small>{{ $levelHint }}</small></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="advisor-nav">
                    <button type="button" class="btn btn-outline-primary d-none" data-wizard-prev><i class='bx bx-left-arrow-alt' aria-hidden="true"></i> Précédent</button>
                    <span class="advisor-counter d-none" data-wizard-counter aria-live="polite"></span>
                    <button type="button" class="btn btn-primary d-none" data-wizard-next>Suivant <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></button>
                    <button type="submit" class="btn btn-accent" data-wizard-submit><i class='bx bx-search-alt' aria-hidden="true"></i> Voir les ordinateurs pour moi</button>
                </div>
            </form>
        </div>
    </section>
@endsection
