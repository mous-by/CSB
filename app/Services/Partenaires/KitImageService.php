<?php

namespace App\Services\Partenaires;

use App\Support\SiteSettings;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Visuels prêts à poster du kit de publication, générés avec GD et la police Lato (resources/fonts, licence OFL) :
 * carré 1080 × 1080 (Facebook, Instagram) et vertical 1080 × 1920 (statuts, stories). Photo du contenu, titre,
 * prix (ancien prix barré en promotion), badge, code promo du partenaire, logo et coordonnées de CSB.
 * Les visuels sont mis en cache sur le disque public et régénérés quand le contenu change.
 */
class KitImageService
{
    public const FORMATS = ['carre' => [1080, 1080], 'story' => [1080, 1920]];

    private const BLUE = [16, 59, 142];

    private const BLUE_DARK = [10, 37, 96];

    private const ORANGE = [245, 130, 32];

    public function __construct(private readonly SiteSettings $settings) {}

    /** Chemin (disque public) du visuel, généré au besoin. */
    public function render(string $key, string $format, array $d): string
    {
        $path = 'kit/'.$key.'-'.$format.'-'.substr(sha1(json_encode([$d['title'], $d['subtitle'], $d['price'], $d['old_price'], $d['badge'], $d['promo_code'], $d['image']?->id, $this->settings->get('contact.phone_1')])), 0, 10).'.png';
        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        [$w, $h] = self::FORMATS[$format];
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        $this->gradient($img, $w, $h);

        $white = imagecolorallocate($img, 255, 255, 255);
        $orange = imagecolorallocate($img, ...self::ORANGE);
        $ink = imagecolorallocate($img, 20, 33, 61);
        $muted = imagecolorallocate($img, 85, 96, 122);
        $story = $format === 'story';
        $pad = 60;

        // Logo et nom en haut.
        $logoH = $story ? 120 : 96;
        $this->paste($img, public_path('assets/site/img/logo-csb.png'), $pad, $story ? 90 : 50, (int) ($logoH * 1.31), $logoH, true);
        $this->text($img, 'COMPUTER SERVICE BARRY', $pad + (int) ($logoH * 1.31) + 24, ($story ? 90 : 50) + (int) ($logoH / 2) + 6, $story ? 38 : 32, $white, 'Black');
        $this->text($img, 'Qualité • Fiabilité • Performance', $pad + (int) ($logoH * 1.31) + 26, ($story ? 90 : 50) + (int) ($logoH / 2) + ($story ? 52 : 44), $story ? 24 : 20, $orange, 'Bold');

        // Carte blanche avec la photo.
        $cardY = $story ? 290 : 190;
        $cardH = $story ? 820 : ($d['promo_code'] ? 380 : 440);
        $this->roundedRect($img, $pad, $cardY, $w - $pad, $cardY + $cardH, 36, $white);
        if ($d['image']) {
            $this->paste($img, Storage::disk('public')->path($d['image']->path), $pad + 30, $cardY + 30, $w - 2 * $pad - 60, $cardH - 60, false);
        }
        if ($d['badge']) {
            $bw = $this->textWidth($d['badge'], $story ? 34 : 28, 'Black') + 56;
            $this->roundedRect($img, $w - $pad - $bw - 20, $cardY - 30, $w - $pad - 20, $cardY + 40, 35, $orange);
            $this->text($img, $d['badge'], $w - $pad - $bw + 8, $cardY + 18, $story ? 34 : 28, imagecolorallocate($img, 27, 18, 3), 'Black');
        }

        // Titre, sous-titre, prix.
        $y = $cardY + $cardH + ($story ? 110 : 76);
        foreach (array_slice($this->wrap($d['title'], $story ? 66 : 52, 'Black', $w - 2 * $pad), 0, 2) as $line) {
            $this->text($img, $line, $pad, $y, $story ? 66 : 50, $white, 'Black');
            $y += $story ? 80 : 60;
        }
        // Sous-titre : toujours en story, en carré seulement s'il n'y a ni promotion ni code promo (place).
        if ($d['subtitle'] && ($story || (! $d['old_price'] && ! $d['promo_code']))) {
            foreach (array_slice($this->wrap($d['subtitle'], $story ? 34 : 30, 'Regular', $w - 2 * $pad), 0, $story ? 2 : 1) as $line) {
                $this->text($img, $line, $pad, $y, $story ? 34 : 30, imagecolorallocate($img, 220, 230, 248), 'Regular');
                $y += $story ? 48 : 42;
            }
            $y += 10;
        }
        if ($d['price'] && $this->textWidth($d['price'], $story ? 72 : 56, 'Black') > $w - 2 * $pad) {
            // Tarif rédigé (« À partir de … par an ») : plus petit, sur deux lignes au plus.
            $y += 10;
            foreach (array_slice($this->wrap($d['price'], $story ? 46 : 38, 'Black', $w - 2 * $pad), 0, 2) as $line) {
                $this->text($img, $line, $pad, $y + ($story ? 46 : 38), $story ? 46 : 38, $orange, 'Black');
                $y += $story ? 60 : 50;
            }
            $d['price'] = null;
        }
        if ($d['price']) {
            $y += $story ? 30 : 10;
            $size = $story ? 72 : 56;
            $this->text($img, $d['price'], $pad, $y + $size - 10, $size, $orange, 'Black');
            if ($d['old_price']) {
                $x = $pad + $this->textWidth($d['price'], $size, 'Black') + 30;
                $this->text($img, $d['old_price'], $x, $y + $size - 14, (int) ($size * .55), imagecolorallocate($img, 200, 210, 230), 'Bold');
                imagesetthickness($img, 5);
                $ow = $this->textWidth($d['old_price'], (int) ($size * .55), 'Bold');
                imageline($img, $x - 4, $y + $size - 28, $x + $ow + 4, $y + $size - 28, imagecolorallocate($img, 225, 29, 72));
            }
            $y += $size + 20;
        }
        if ($d['promo_code']) {
            $label = "CODE {$d['promo_code']} : -{$d['promo_discount']} %";
            $tw = $this->textWidth($label, $story ? 36 : 30, 'Black') + 50;
            $this->roundedRect($img, $pad, $y, $pad + $tw, $y + ($story ? 70 : 60), 16, $white);
            $this->text($img, $label, $pad + 25, $y + ($story ? 48 : 41), $story ? 36 : 30, imagecolorallocate($img, ...self::BLUE), 'Black');
        }

        // Story : points forts (caractéristiques, fonctionnalités, technologies) dans l'espace restant.
        if ($story && $d['points']) {
            $y += 50;
            foreach (array_slice($d['points'], 0, 4) as $point) {
                if ($y > $h - 260) {
                    break;
                }
                $this->roundedRect($img, $pad, $y - 26, $pad + 30, $y + 4, 15, $orange);
                foreach (array_slice($this->wrap($point, 34, 'Bold', $w - 2 * $pad - 50), 0, 1) as $line) {
                    $this->text($img, $line, $pad + 48, $y + 2, 34, $white, 'Bold');
                }
                $y += 62;
            }
        }

        // Bandeau bas : coordonnées.
        $barH = $story ? 170 : 110;
        imagefilledrectangle($img, 0, $h - $barH, $w, $h, imagecolorallocate($img, ...self::BLUE_DARK));
        imagefilledrectangle($img, 0, $h - $barH, $w, $h - $barH + 8, $orange);
        $phones = implode(' / ', array_filter([$this->settings->get('contact.phone_1'), $this->settings->get('contact.phone_2')]));
        $this->text($img, 'Contact & WhatsApp : '.$phones, $pad, $story ? $h - 95 : $h - (int) ($barH / 2) + 14, $story ? 40 : 32, $white, 'Bold');
        if ($story) {
            $this->text($img, 'Plus d\'infos et commande : lien en description', $pad, $h - 40, 28, $orange, 'Bold');
        }

        Storage::disk('public')->makeDirectory('kit');
        imagepng($img, Storage::disk('public')->path($path), 6);

        return $path;
    }

    private function font(string $weight): string
    {
        return resource_path("fonts/Lato-{$weight}.ttf");
    }

    private function text(GdImage $img, string $text, int $x, int $y, int $px, int $color, string $weight): void
    {
        // Lato n'a pas d'émojis : on les retire plutôt que d'afficher des carrés.
        $text = trim((string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text));
        if ($text !== '') {
            imagettftext($img, $px * 0.75, 0, $x, $y, $color, $this->font($weight), $text);
        }
    }

    private function textWidth(string $text, int $px, string $weight): int
    {
        $box = imagettfbbox($px * 0.75, 0, $this->font($weight), $text);

        return abs($box[2] - $box[0]);
    }

    /** @return list<string> */
    private function wrap(string $text, int $px, string $weight, int $max): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && $this->textWidth($try, $px, $weight) > $max) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function gradient(GdImage $img, int $w, int $h): void
    {
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $c = imagecolorallocate($img, (int) (29 + (10 - 29) * $t), (int) (91 + (37 - 91) * $t), (int) (196 + (96 - 196) * $t));
            imageline($img, 0, $y, $w, $y, $c);
        }
    }

    private function roundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    /** Colle une image dans un cadre, proportions gardées (centrée), ou au plus près du coin si $left. */
    private function paste(GdImage $img, string $file, int $x, int $y, int $bw, int $bh, bool $left): void
    {
        if (! is_file($file)) {
            return;
        }
        $src = @imagecreatefromstring((string) file_get_contents($file));
        if (! $src instanceof GdImage) {
            return;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = min($bw / $sw, $bh / $sh);
        $dw = (int) ($sw * $scale);
        $dh = (int) ($sh * $scale);
        imagecopyresampled($img, $src, $left ? $x : $x + (int) (($bw - $dw) / 2), $y + (int) (($bh - $dh) / 2), 0, 0, $dw, $dh, $sw, $sh);
    }
}
