<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Protection anti-spam des formulaires publics (cahier §10), sans service externe ni captcha : un champ piège invisible
 * (que les robots remplissent) et un jeton chiffré de l'heure d'affichage du formulaire (un envoi en moins de
 * MIN_SECONDS est le fait d'un robot). S'ajoute à la limitation de débit (throttle:demandes).
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

        return now()->getTimestamp() - $shownAt < self::MIN_SECONDS;
    }
}
