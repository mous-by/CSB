<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Protection anti-spam des formulaires publics (cahier §10), sans service externe ni captcha : un champ piège invisible
 * (que les robots remplissent) et un jeton chiffré de l'heure d'affichage du formulaire (un envoi en moins de
 * MIN_SECONDS est le fait d'un robot). S'ajoute à la limitation de débit (throttle:demandes).
 * Le contenu est aussi filtré : e-mail qui imite le domaine du site (« domains@search-computerservicebarry.com »,
 * arnaque au référencement) et domaines ou mots de la liste « Anti-spam » (Pages et paramètres).
 * Un envoi refusé reçoit une fausse réussite : rien n'est enregistré ni notifié.
 */
class SpamGuard
{
    public const HONEYPOT = 'site_web';

    public const TOKEN = '_form_t';

    public const MIN_SECONDS = 3;

    /** Jeton à placer dans le formulaire. */
    public static function token(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    /** Vrai si l'envoi ressemble à celui d'un robot. */
    public static function isSpam(Request $request): bool
    {
        if (filled($request->input(self::HONEYPOT))) {
            return true;
        }

        try {
            $shownAt = (int) Crypt::decryptString((string) $request->input(self::TOKEN));
        } catch (Throwable) {
            return true;
        }

        if (now()->getTimestamp() - $shownAt < self::MIN_SECONDS) {
            return true;
        }

        return self::isBlocked($request);
    }

    /** Contenu refusé : e-mail qui imite le domaine du site, ou domaine / mot de la liste « Anti-spam ». */
    public static function isBlocked(Request $request): bool
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $domain = str_contains($email, '@') ? Str::afterLast($email, '@') : '';
        if ($domain !== '' && self::imitatesSiteDomain($domain)) {
            Log::notice("Formulaire refusé (anti-spam) : domaine {$domain} qui imite celui du site.");

            return true;
        }

        $blocked = app(SiteSettings::class)->lines('antispam.blocked');
        if ($blocked === []) {
            return false;
        }
        $text = mb_strtolower(collect($request->except([self::HONEYPOT, self::TOKEN, '_token']))->flatten()->filter(fn ($value) => is_string($value))->implode(' '));
        foreach ($blocked as $entry) {
            $entry = mb_strtolower(trim($entry));
            if ($entry !== '' && str_contains($text, $entry)) {
                Log::notice("Formulaire refusé (anti-spam) : « {$entry} » de la liste de blocage.");

                return true;
            }
        }

        return false;
    }

    /** « search-computerservicebarry.com » contient le nom du site sans être son domaine (ni un sous-domaine). */
    private static function imitatesSiteDomain(string $domain): bool
    {
        $host = preg_replace('/^www\./', '', mb_strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)));
        $name = Str::before($host, '.');
        if (mb_strlen($name) < 6 || $name === 'localhost') {
            return false;
        }

        return str_contains($domain, $name) && $domain !== $host && ! str_ends_with($domain, '.'.$host);
    }
}
