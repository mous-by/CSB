<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Rendu Markdown des contenus saisis dans l'administration (pages légales, descriptions, articles) : le HTML brut est
 * supprimé et les liens dangereux (javascript:, data:) refusés, pour qu'un contenu ne puisse pas injecter de script
 * (cahier §10, XSS). Les liens externes s'ouvrent dans le même onglet ; aucune ressource externe n'est chargée.
 */
class Markdown
{
    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return (string) Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
    }

    /** Texte brut (extrait, méta-description) : Markdown retiré, espaces normalisés, coupé à $limit caractères. */
    public static function toText(?string $markdown, int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags(self::toHtml($markdown))) ?? '');

        return Str::limit(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $limit);
    }
}
