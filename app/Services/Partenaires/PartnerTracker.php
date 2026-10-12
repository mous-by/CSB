<?php

namespace App\Services\Partenaires;

use App\Models\Partenaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

/**
 * Suivi des partenaires : un visiteur arrivé par `?ref=CODE` reçoit un cookie (durée réglée sur le partenaire, 30 jours
 * par défaut) ; ses demandes, son compte client et ses commandes sont alors attribués au partenaire. Le dernier lien
 * suivi l'emporte. Une visite est comptée une fois par jour et par visiteur (session).
 */
class PartnerTracker
{
    public const COOKIE = 'csb_ref';

    public function track(Request $request): void
    {
        $code = $request->query('ref');
        if (! is_string($code) || $code === '' || strlen($code) > 40) {
            return;
        }
        $partenaire = Partenaire::active()->where('ref_code', mb_strtolower($code))->first();
        if (! $partenaire) {
            return;
        }

        Cookie::queue(self::COOKIE, $partenaire->ref_code, $partenaire->cookie_days * 24 * 60);
        $request->attributes->set('partenaire', $partenaire);

        $seen = 'partenaire_vu.'.$partenaire->id.'.'.today()->toDateString();
        if ($request->hasSession() && ! $request->session()->get($seen)) {
            $request->session()->put($seen, true);
            DB::table('partenaire_visites')->upsert(
                [['partenaire_id' => $partenaire->id, 'day' => today()->toDateString(), 'visits' => 1]],
                ['partenaire_id', 'day'],
                ['visits' => DB::raw('visits + 1')],
            );
        }
    }

    /** Partenaire à qui attribuer l'action du visiteur (lien suivi dans cette requête ou cookie), ou null. */
    public function current(Request $request): ?Partenaire
    {
        if ($request->attributes->get('partenaire') instanceof Partenaire) {
            return $request->attributes->get('partenaire');
        }
        $code = $request->cookie(self::COOKIE);

        return is_string($code) && $code !== '' ? Partenaire::active()->where('ref_code', $code)->first() : null;
    }

    /** Partenaire d'un code promo utilisable aujourd'hui, ou null. */
    public function byPromoCode(?string $code): ?Partenaire
    {
        $code = mb_strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }
        $partenaire = Partenaire::where('promo_code', $code)->first();

        return $partenaire?->promoActive() ? $partenaire : null;
    }
}
