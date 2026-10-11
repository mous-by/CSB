{{-- Fin d'un formulaire public : information sur les données (cahier §6.2), anti-spam (SpamGuard), envoi. --}}
<div class="col-12">
    <div class="form-check">
        <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" id="f-consent" name="consent" value="1" @checked(old('consent')) required @error('consent') aria-describedby="f-consent-error" aria-invalid="true" @enderror>
        <label class="form-check-label small" for="f-consent">
            J'accepte que mes informations soient utilisées uniquement pour répondre à ma demande (<a href="{{ route('site.privacy') }}" target="_blank" rel="noopener">politique de confidentialité</a>).
        </label>
    </div>
    @error('consent')<div class="invalid-feedback d-block" id="f-consent-error">{{ $message }}</div>@enderror
</div>
<div class="hp-field" aria-hidden="true">
    <label for="f-site-web">Ne pas remplir ce champ</label>
    <input type="text" id="f-site-web" name="{{ \App\Support\SpamGuard::HONEYPOT }}" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN }}" value="{{ \App\Support\SpamGuard::token() }}">
<input type="hidden" name="source_url" value="{{ url()->previous() !== url()->current() ? url()->previous() : '' }}">
<div class="col-12 d-flex flex-wrap align-items-center gap-3">
    <button type="submit" class="btn btn-accent btn-lg"><i class='bx bx-send' aria-hidden="true"></i> {{ $submit ?? 'Envoyer' }}</button>
    <span class="small text-muted">Réponse rapide · Vos données restent confidentielles</span>
</div>
