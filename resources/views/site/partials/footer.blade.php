{{-- Pied de page (maquette CSB 2026-10-11) : identité, liens utiles, services, contact, inscription aux actualités,
     pages légales. $footerServices : services publiés (AppServiceProvider). --}}
@php
    $links = collect([
        ['site.home', 'Accueil'], ['site.about', 'À propos'], ['site.services', 'Nos services'], ['site.logiciels.index', 'Nos solutions'],
        ['site.materiels.index', 'Matériel informatique'], ['site.conseiller', 'Choisir mon ordinateur'], ['site.realisations.index', 'Nos réalisations'], ['site.temoignages.index', 'Témoignages'], ['site.actualites.index', 'Actualités'], ['site.contact', 'Contact'],
    ])->filter(fn ($item) => Route::has($item[0]));
    $phones = array_values(array_filter([$site->get('contact.phone_1'), $site->get('contact.phone_2')]));
    $tel = \App\Support\PhoneNumber::normalize($phones[0] ?? null);
@endphp
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <a class="brand-logo footer-brand" href="{{ route('site.home') }}">
                    <img src="{{ $site->imageUrl('site.logo', 'assets/site/img/logo-csb.png') }}" alt="" width="70" height="54" loading="lazy">
                    <span class="brand-text"><strong><span>COMPUTER SERVICE</span> <em>BARRY</em></strong><small>{{ $site->get('company.slogan') }}</small></span>
                </a>
                <p class="mt-3 mb-0">{{ $site->get('footer.text') }}</p>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h2>Liens utiles</h2>
                <ul class="footer-cols">
                    @foreach ($links as [$route, $label])
                        <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h2>Nos services</h2>
                <ul>
                    @forelse ($footerServices ?? [] as $service)
                        <li><a href="{{ route('site.services') }}#{{ $service->slug }}">{{ $service->title }}</a></li>
                    @empty
                        <li><a href="{{ route('site.services') }}">Tous nos services</a></li>
                    @endforelse
                </ul>
            </div>
            <div class="col-lg-3">
                <h2>Contact</h2>
                <ul class="footer-contact">
                    @if ($phones)
                        <li><i class='bx bxs-phone' aria-hidden="true"></i>@if ($tel)<a href="tel:{{ $tel }}">{{ implode(' / ', $phones) }}</a>@else<span>{{ implode(' / ', $phones) }}</span>@endif</li>
                    @endif
                    @if ($site->get('contact.email'))
                        <li><i class='bx bxs-envelope' aria-hidden="true"></i><a href="mailto:{{ $site->get('contact.email') }}">{{ $site->get('contact.email') }}</a></li>
                    @endif
                    @if ($site->get('contact.address') || $site->get('contact.city'))
                        <li><i class='bx bxs-map' aria-hidden="true"></i><span>{!! nl2br(e($site->get('contact.address') ?: $site->get('contact.city'))) !!}</span></li>
                    @endif
                    @if ($site->get('contact.hours'))
                        <li><i class='bx bxs-time-five' aria-hidden="true"></i><span>{!! nl2br(e($site->get('contact.hours'))) !!}</span></li>
                    @endif
                </ul>
                <div class="footer-social">
                    @foreach ($site->socialLinks() as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="social-{{ $network }}" aria-label="{{ ucfirst($network) }}"><i class='bx bxl-{{ $network }}' aria-hidden="true"></i></a>
                    @endforeach
                    @if ($site->whatsappLink())
                        <a href="{{ $site->whatsappLink('Bonjour CSB,') }}" target="_blank" rel="noopener" class="social-whatsapp" aria-label="WhatsApp"><i class='bx bxl-whatsapp' aria-hidden="true"></i></a>
                    @endif
                </div>
                @if (Route::has('site.newsletter.subscribe'))
                    <form method="POST" action="{{ route('site.newsletter.subscribe') }}" class="newsletter-form" data-ajax-form>
                        @csrf
                        <label for="newsletter-email" class="newsletter-title">Restez informé de nos actualités</label>
                        <div class="newsletter-row">
                            <input type="email" id="newsletter-email" name="email" placeholder="Votre adresse e-mail" required autocomplete="email">
                            <button type="submit">S'inscrire</button>
                        </div>
                        <div class="hp-field" aria-hidden="true"><input type="text" name="{{ \App\Support\SpamGuard::HONEYPOT }}" tabindex="-1" autocomplete="off"></div>
                        <input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN }}" value="{{ \App\Support\SpamGuard::token() }}">
                        <small>Désinscription en un clic à tout moment. <a href="{{ route('site.privacy') }}">Confidentialité</a></small>
                    </form>
                @endif
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} {{ $site->get('company.name') }} ({{ $site->get('company.short_name') }}). Tous droits réservés.</span>
            <nav aria-label="Informations légales">
                <a href="{{ route('site.privacy') }}">Politique de confidentialité</a>
                <a href="{{ route('site.legal') }}">Mentions légales</a>
                @if (Route::has('site.sitemap'))
                    <a href="{{ route('site.sitemap') }}">Plan du site</a>
                @endif
                <a href="#pwa-install" data-pwa-open hidden><i class='bx bx-download' aria-hidden="true"></i> Installer l'application</a>
            </nav>
        </div>
    </div>
</footer>
